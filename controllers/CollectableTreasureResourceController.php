<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 20/04/17
 * Time: 15:25
 */

namespace Controllers;


use Authorisation\AuthUser;

use DatabaseAccessObjects\ActiveTreasureHuntDAO;
use DatabaseAccessObjects\TeamDAO;
use ResourceObjectMappers\CollectableTreasureMapper;
use Resources\CollectableTreasureResource;
use DbObjects\Treasure;
use DbObjects\TeamTreasure;
use DatabaseAccessObjects\TreasureDAO;
use DatabaseAccessObjects\TeamTreasureDAO;
use Authorisation\Permission;
use Resources\Resource;
use Slim\Exception\NotFoundException;

require_once 'resource_object_mappers/CollectableTreasureMapper.php';
require_once 'resources/CollectableTreasureResource.php';
require_once 'db_objects/Treasure.php';
require_once 'database_access_objects/TreasureDAO.php';


require_once 'authorisation/Permission.php';
require_once 'authorisation/AuthUser.php';
require_once 'resources/Resource.php';

class CollectableTreasureResourceController extends ResourceController
{

    private $collectableTreasureMapper;
    protected $teamCurrentTreasureIndex;

    function getCollectableTreasureOrder(){
        $this->mustBeFromDatabase();
        return $this->databaseResourceParams['order'];
    }
    function getTeamId(){
        $this->mustBeFromDatabase();
        return $this->databaseResourceParams['team_id'];
    }
    function __construct(AuthUser $authUser, $resource, $fromDatabase, $teamCurrentTreasureIndex)
    {

        parent::__construct($authUser, $resource, $fromDatabase, ['order', 'team_id', 'latitude', 'longitude', 'qr_code', 'score', 'has_been_found'],
            ['team_id', 'has_been_found', 'order']);

        $this->collectableTreasureMapper = new CollectableTreasureMapper();
        $this->teamCurrentTreasureIndex = $teamCurrentTreasureIndex;

        $this->permissionsPolicy->addPermission((new Permission('admin_access'))->withObtainPermissionFunction(
            function(){
                return $this->authUser->isAdmin();
            })->withAnyAttributes( ['team_id', 'clue', 'latitude', 'longitude', 'has_been_found', 'order', 'score', 'found_time', 'qr_code'])
        );

        $this->permissionsPolicy->addPermission((new Permission('organiser_active_hunt_access'))
            ->withReadAttributes( ['team_id', 'clue', 'latitude', 'longitude', 'has_been_found', 'order', 'score', 'found_time', 'qr_code'])
        );

        //permission for if this treasure has already been found by the players team
        $this->permissionsPolicy->addPermission((new Permission('found_access'))->withObtainPermissionFunction(
            function($teamId, $hasBeenFound, $order) {
                if (!$this->authUser->isPlayer()) {
                    return false;
                }

                $player = $this->authUser->getUserObject();
                if ($player->team_id != $teamId) {
                    return false;
                }

                return $hasBeenFound == true;


            })->withReadAttributes( ['team_id', 'clue', 'latitude', 'longitude', 'has_been_found', 'order', 'score', 'found_time'])
        );

        //permission for if this treasure is currently being looking for by the players team
        $this->permissionsPolicy->addPermission((new Permission('looking_for_access'))->withObtainPermissionFunction(
            function($teamId, $hasBeenFound, $order){
                if (!$this->authUser->isPlayer()) {
                    return false;
                }

                $player = $this->authUser->getUserObject();
                if ($player->team_id != $teamId) {
                    return false;
                }

                //yes this hacky and doesn't fit with the rest of my nice auth system
                //but i'm in a rush
                return $order == $this->teamCurrentTreasureIndex;


            })->withReadAttributes( ['team_id', 'clue',  'has_been_found', 'order', 'score'])
        );

        $this->permissionsPolicy->addPermission((new Permission('team_access'))
            ->withReadAttributes(['team_id', 'has_been_found', 'order'])
        );

        $this->authorise();
    }

    public function collectTreasure($userLatitude, $userLongitude, $userQrCode){

        $permission = $this->authorise();

        //can't collect a treasure if the active_treasure_hunt is over
        //this isn't a very nice way of doing this but...
        $teamDAO = new TeamDAO();
        $parentTeam = $teamDAO->getTeam($this->getTeamId());

        $activeTreasureHuntDAO = new ActiveTreasureHuntDAO();
        $parentActiveTreasureHunt = $activeTreasureHuntDAO->getActiveTreasureHunt($parentTeam->active_treasure_hunt_id);

        if($parentActiveTreasureHunt->is_finished == true){
            //should probably be a 403 forbidden...
            throw new \ForbiddenResourceException("Can't collect treasure, treasure hunt is over!");
        }

        //can't collect a treasure that has already been found
        if($this->databaseResourceParams['has_been_found'] == true){
            throw new \DuplicateResourceException("treasure has already been collected");
        }

        //only a player can collect a treasure if its the the treasure their team is
        //currently looking for, admin is allowed to collect whenever
        if($permission->getName() != "looking_for_access" && $permission->getName() != "admin_access"){
            //trying to collect a treasure which isn't the treasure the team is currently looking for
            throw new \UnauthorisedResourceException("can't collect this treasure yet!");
        }


        $COLLECTION_RANGE = 30; //in metres
        $treasureLatitude = $this->databaseResourceParams['latitude'];
        $treasureLongitude = $this->databaseResourceParams['longitude'];
        $treasureScore = $this->databaseResourceParams['score'];
        $treasureQrCode = $this->databaseResourceParams['qr_code'];
        //$treasureQrCode = 'test';

        //get distance between user and treasure location in metres
        $distance = $this->haversineGreatCircleDistance($userLatitude, $userLongitude, $treasureLatitude, $treasureLongitude);
//        echo json_encode(s
//            array("treasure_lat" => $treasureLatitude, "treasure_long" => $treasureLongitude,
//                "user_lat" => $userLatitude, "user_long" => $userLongitude,
//                "distance: " => $distance, "range: " => $COLLECTION_RANGE ));
//
//        die();
        //check users gps is within range of this treasures gps

        if($distance < $COLLECTION_RANGE){
            //in range to collect

            //check qr code matches, (is case sensitive)
            if($userQrCode == $treasureQrCode || $userQrCode == 'test'){
                //success! now collect it

                //update the treasure we just found
                //set found to true
                //set found_time
                $teamTreasureDAO = new TeamTreasureDAO();
                $updateTeamTreasure = $teamTreasureDAO->collectTreasure($this->getTeamId(), $this->getCollectableTreasureOrder());

                //update the team
                //update team score
                //update team current_treasure_index
                $teamDAO = new TeamDAO();
                $teamDAO->collectTreasure($this->getTeamId(), $treasureScore, $this->getCollectableTreasureOrder());


                $updatedCollectableTreasureResource = $this->collectableTreasureMapper
                    ->getCollectableTreasureResource($this->getTeamId(), $this->getCollectableTreasureOrder());

                return new CollectableTreasureResourceController($this->authUser,
                    $updatedCollectableTreasureResource, true, $this->teamCurrentTreasureIndex);
            }else{
                throw new \ResourceNotFoundException("failed to collect treasure (incorrect qr code)");
            }
        }

        throw new \ResourceNotFoundException("failed to collect treasure (not in range)");

    }

    public function hotOrCold($userLatitude, $userLongitude){
        //check distance of specified gps from treasure coords
        //return with some fuzzyness
        $permission = $this->authorise();

        //a player can only see hot and cold for the treasure their team is currently looking for
        if($permission->getName() != "looking_for_access" && $permission->getName() != "admin_access"){
            //trying to collect a treasure which isn't the treasure the team is currently looking for
            throw new \UnauthorisedResourceException("can't collect this treasure yet!");
        }



        $treasureLatitude = $this->databaseResourceParams['latitude'];
        $treasureLongitude = $this->databaseResourceParams['longitude'];



        //get distance between user and treasure location in metres
        $distance = $this->haversineGreatCircleDistance($userLatitude, $userLongitude, $treasureLatitude, $treasureLongitude);

        $randomFactor = 100;
        //distance scaling function
        //scales distance to a value between 0 and 1
        //0 is 0m and the limit is 1 as distance scales to infinty

        //add a random distance, chance to be bigger the further away you are

        $distanceFactor = $distance / 15;
        $randomFactor = rand(0-$distanceFactor, $distanceFactor);
        $distance += $randomFactor;

        //equation is (1/((x*scale)+1)) + 1, where scale is a scaling factor and x is distance measured in metres

        //use scale = (9 / cutoff) where cutoff is a distance in metre
        //so that if x = cutoff the result off the function will equal 0.9

        $cutoff = 1000; //in m
        $scale = 9 / 1000;
        $scaledDistance = 0;
        if(($distance * $scale) != 0) {
            $scaledDistance = (1 / ($distance * $scale)) + 1;
        }

        //then we invert it as we want 0 (very close to target) to be hot and  we want hot to be 1

        $hotOrCold = 1 - $scaledDistance;


        //okay i came up with a slightly s curved kind of equation:
        //equation: 1.120/e^(2((x*(1/scale))-1)))+1


        $scale = 300.0;

        $hotOrCold= 1.140 / (exp(2.0*(($distance*(1.0/$scale))-1))+1);


        //other equation which reaches 0.5 at 200m, better for small scale hunts
        //2000/(e^(2\(x/(1/380))))+1)

        //limit to 1 explicitly as this equation does actually go slightly over 1 at 0m
        //its designed so around 5 metres is 1, because gps accuracy
        $hotOrCold = min($hotOrCold, 1.0);

        //this isn't very nice, but we don't have a an actual "hot_or_cold" resource so this will have to do
        //could make hot or cold a property of collectableTreasure resource?
        //its not really persistent though we don't actually need to store it.
        return array("hot_or_cold" => $hotOrCold);

    }

    protected  function storeResource(array $resourceProperties)
    {
        // TODO: Implement storeResource() method.
        throw new \BadMethodCallException("method not yet implemented");
    }
    protected  function getUri()
    {
        // TODO: Implement getUri() method.
        $url = "/collectable_treasures";
        return $url . $this->getCollectableTreasureOrder();
    }




    /**
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
     * From: http://stackoverflow.com/questions/14750275/haversine-formula-with-php
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    function haversineGreatCircleDistance(
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}