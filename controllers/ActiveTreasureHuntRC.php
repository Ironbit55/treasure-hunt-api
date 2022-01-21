<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 26/03/2017
 * Time: 22:57
 */

namespace Controllers;

use Controllers\ResourceController;
use DatabaseAccessObjects\TeamTreasureDAO;
use DatabaseAccessObjects\TreasureHuntTemplateDAO;


use DbObjects\ActiveTreasureHunt;
use DatabaseAccessObjects\ActiveTreasureHuntDAO;
use DbObjects\Team;
use DatabaseAccessObjects\TeamDAO;
use DbObjects\TeamTreasure;
use ResourceObjectMappers\TeamMapper;
use DatabaseAccessObjects\PlayerDAO;
use Controllers\PlayerResourceController;
use ResourceObjectMappers\ActiveTreasureHuntMapper;
use ResourceObjectMappers\TreasureHuntTemplateMapper;
use Resources\ActiveTreasureHuntResource;
use Authorisation\Permission;

use Authorisation\AuthUser;
use Resources\TeamResource;
use DatabaseAccessObjects\TreasureDAO;
use DbObjects\Treasure;

require_once 'db_objects/ActiveTreasureHunt.php';
require_once 'database_access_objects/ActiveTreasureHuntDAO.php';
require_once 'db_objects/Team.php';
require_once 'database_access_objects/TeamDAO.php';
require_once 'resource_object_mappers/TeamMapper.php';
require_once 'database_access_objects/PlayerDAO.php';
require_once 'controllers/ResourceController.php';
require_once 'controllers/PlayerResourceController.php';

//active treasure hunt resource controller
class ActiveTreasureHuntRC extends ResourceController
{
    private $activeTreasureHuntDAO;
    private $activeTreasureHuntMapper;
    private $teamMapper;

    function getActiveTreasureHuntId(){
        $this->mustBeFromDatabase();
        return $this->databaseResourceParams['id'];
    }

    /*
    * @param AuthUser $authUser: the currently authenticated user
    * @param $activeTreasureHuntId: the id of the active treasure hunt resource to create, can be null if $player is set
    * @param ActiveTreasureHunt $activeTreasureHunt: the  object used to create this resource,
    *                          if null, will be obtained from database using the id
    */
    function __construct($authUser, ActiveTreasureHuntResource $activeTreasureHuntResource, $fromDatabase)
    {

        $this->teamMapper = new TeamMapper();
        $this->activeTreasureHuntDAO = new ActiveTreasureHuntDAO();
        $this->activeTreasureHuntMapper = new ActiveTreasureHuntMapper();

        parent::__construct($authUser, $activeTreasureHuntResource, $fromDatabase, array('id', 'start_time', 'is_finished', 'start_time', 'finish_time', 'is_started'), array('id', 'organiser_id'));

        $this->defineSubresource('teams', function(){

            return $this->teamMapper->getCollection($this->getActiveTreasureHuntId());

        }, function($teamResourceData, $fromDatabase){
            $childTeamResourceData = $this->setChildParent($teamResourceData, 'active_treasure_hunt_id', $this->getActiveTreasureHuntId());

            $teamResource = new TeamResource($childTeamResourceData);

            return new TeamResourceController($this->authUser, $teamResource, $fromDatabase);

        });

        $this->permissionsPolicy->addPermission((new Permission('hunt_access'))->withObtainPermissionFunction(
            function ($activeTreasureHuntId) {
                if ($this->authUser->isPlayer()) {
                    $authPlayer = $this->authUser->getUserObject();
                    $teamDAO = new TeamDAO();
                    $authPlayerTeam = $teamDAO->getTeam($authPlayer->team_id);
                    if (!$authPlayerTeam) {
                        return false;
                    }

                    return $authPlayerTeam->active_treasure_hunt_id == $activeTreasureHuntId;
                }

                return false;
            })->withReadAttributes(['id', 'name',  'is_started', 'is_finished', 'start_time', 'finish_time', 'teams'])
        );

        $this->permissionsPolicy->addPermission((new Permission('admin_access'))->withObtainPermissionFunction(
            function ($activeTreasureHuntId) {
                if ($this->authUser->isAdmin()) {
                    return true;
                }

                return false;
            })->withAnyAttributes(['id', 'name', 'is_started', 'is_finished', 'start_time', 'finish_time', 'organiser_id', 'treasure_hunt_template_id', 'teams'])
        );

        $this->permissionsPolicy->addPermission((new Permission('organiser_active_hunt_access'))->withObtainPermissionFunction(
            function ($activeTreasureHuntId, $organiserId) {
                if ($this->authUser->isOrganiser()) {

                    $authOrganiser = $this->authUser->getUserObject();

                    return $organiserId == $authOrganiser->id;
                }
                return false;
            })->withAnyAttributes(['id', 'name', 'is_started', 'is_finished', 'start_time', 'finish_time', 'organiser_id', 'treasure_hunt_template_id', 'teams'])
        );

        $this->permissionsPolicy->addPermission((new Permission('organiser_access'))
            ->withCreateAttributes(['id', 'name', 'organiser_id', 'treasure_hunt_template_id', 'teams'])
        );



        $this->authorise();

    }

    function addTeam($teamId){
        $teamResource = $this->teamMapper->getResource($teamId, $this->getActiveTreasureHuntId());
        return $this->addSubresource('teams', $teamResource->getProperties(), true);
    }

    function getUri()
    {
        $url = "/active_treasure_hunts/";
        return $url . $this->getActiveTreasureHuntId();
        // TODO: Implement getUri() method.
    }

    //if $useDynamicAllocation is true (1) then order collectable_treasures randomly
    //if its false give collectable_treasures the default order
    //of their base treasure
    public function start($useDynamicAllocation)
    {
        //if from db and
        //if not already started
        $this->mustBeFromDatabase();



        if ($this->databaseResourceParams['is_started'] == true) {
            throw new \DuplicateResourceException("can't start already started active treasure hunt");
        }

        $teamDAO = new TeamDAO();
        $treasureDAO = new TreasureDAO();
        //get all teams belonging to activeTreasureHunt
        $teams = $teamDAO->getAllTeamsInActiveTreasureHunt($this->getActiveTreasureHuntId());

        //get all treasures belonging to treasure_hunt_template_id
        $treasures = $treasureDAO->getTreasures($this->resource->getProperty('treasure_hunt_template_id'));

        $teamTreasures = [];
        $defaultScore = 10;

        //sort treasures in ascending order base on default_order field
        usort($treasures, "sortTreasuresInAscendingOrder");

        $treasureCount = 0;
        //get all treasures belonging to treasure_hunt_template_id
        //foreach team and treasure, add team_id, and treasure_id, order
        //score -> 10
        if ($useDynamicAllocation == false) {

            //give treasures their default_order
            foreach ($teams as $team) {
                foreach ($treasures as $treasure) {
                    $teamTreasure = new TeamTreasure();
                    $teamTreasure->team_id = $team->id;
                    $teamTreasure->treasure_id = $treasure->id;
                    $teamTreasure->order = $treasure->default_order;
                    $teamTreasure->score = $defaultScore;

                    $teamTreasures[] = $teamTreasure;

                }
            }
        }else{
            //this stores the treasure available to chose from randomly
            $availableTreasures = $treasures;
            //remove the last treasure as we want this to be the same for all teams
            $lastTreasure = $availableTreasures[count($availableTreasures) - 1];
            unset($availableTreasures[count($availableTreasures) - 1]);

            foreach ($teams as $team) {
                $treasuresStack = $availableTreasures;

                //loop though all treasure
                $i = 0;
                foreach ($treasures as $treasure) {

                    $selectedTreasure = null;
                    if($i == count($treasures) - 1){
                        //last treasure is the same for all teams
                        $selectedTreasure = $treasures[count($treasures) - 1];
                    }else {
                        //pick a treasure randomly from the available treasures otherwise
                        $randomTreasureIndex = random_int(0, count($treasuresStack) - 1);
                        $selectedTreasure = $treasuresStack[$randomTreasureIndex];
                    }


                    //set team treasure details using that treasure with order $i
                    $teamTreasure = new TeamTreasure();
                    $teamTreasure->team_id = $team->id;
                    $teamTreasure->order = $i;
                    $teamTreasure->score = $defaultScore;
                    $teamTreasure->treasure_id = $selectedTreasure->id;

                    $teamTreasures[] = $teamTreasure;

                    //delete the treasure we just picked and reorder indexes
                    array_splice($treasuresStack, $randomTreasureIndex, 1);
                    $i++;
                }

            }

        }

        //sort treasures in ascending order based on order field
        usort($teamTreasures, "sortTeamTreasuresInAscendingOrder");

        $teamTreasureDAO = new TeamTreasureDAO();
        $teamTreasureDAO->createMultipleTeamTreasure($teamTreasures);

        //start active_treasure_hunt
        //set started to true
        //set start time
        $startedActiveTreasureHunt = $this->activeTreasureHuntDAO->startActiveTreasureHunt($this->getActiveTreasureHuntId());

        if(!$startedActiveTreasureHunt){
            throw new \Exception("failed to update active_treasure_hunt");
        }

        $startedActiveTreasureHuntResource = $this->activeTreasureHuntMapper->resource_encode($startedActiveTreasureHunt);

        return new ActiveTreasureHuntRC($this->authUser, $startedActiveTreasureHuntResource, true);

    }

    function sortTreasuresInAscendingOrder(Treasure $treasure1, Treasure $treasure2)
    {

        if ($treasure1->default_order == $treasure2->default_order) {
            return 0;
        }
        return ($treasure1->default_order < $treasure2->default_order) ? -1 : 1;

    }

    function sortTeamTreasuresInAscendingOrder(TeamTreasure $teamTreasure1, TeamTreasure $teamTreasure2)
    {

        if ($teamTreasure1->order == $teamTreasure2->order) {
            return 0;
        }
        return ($teamTreasure1->order < $teamTreasure2->order) ? -1 : 1;

    }

    public function stop(){
        $this->mustBeFromDatabase();
        if($this->databaseResourceParams['is_started'] == false){
            throw new \DuplicateResourceException("can't stop an active treasure hunt which is not yet started");
        }
        if($this->databaseResourceParams['is_finished'] == true){
            throw new \DuplicateResourceException("can't stop already ended active treasure hunt");
        }

        $activeTreasureHunt = $this->activeTreasureHuntDAO->stopActiveTreasureHunt($this->getActiveTreasureHuntId());
        $activeTreasureHuntResource = $this->activeTreasureHuntMapper->resource_encode($activeTreasureHunt);
        return new ActiveTreasureHuntRC($this->authUser, $activeTreasureHuntResource, true);

    }

    function storeResource(array $resourceProperties)
    {

        /*
         * checking this resource treasure hunt template id is valid
         * before we can store it
         */
        $treasureHuntTemplateId = $this->resource->getProperty('treasure_hunt_template_id');

        $treasureHuntTemplateMapper = new TreasureHuntTemplateMapper();

        $errorMessage = "could not find treasure_hunt_template with id: \"$treasureHuntTemplateId\"" .
        "that belongs to this organiser";

        try {
            $treasureHuntTemplateResource = $treasureHuntTemplateMapper->getTreasureHuntTemplate($treasureHuntTemplateId);
        }catch(\ResourceNotFoundException $e){
            throw new \UnprocessableResourceException($errorMessage);
        }

        if($treasureHuntTemplateResource->getProperty('organiser_id') != $this->resource->getProperty('organiser_id')){
            throw new \UnprocessableResourceException($errorMessage);
        }


        $activeTreasureHuntResource = new ActiveTreasureHuntResource($resourceProperties);

        $createdActiveTreasureHuntResource = $this->activeTreasureHuntMapper->storeResource($activeTreasureHuntResource);

        return new ActiveTreasureHuntRC($this->authUser, $createdActiveTreasureHuntResource, true);

    }
}