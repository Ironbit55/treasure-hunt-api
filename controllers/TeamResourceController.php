<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Controllers;

use Authorisation\AuthUser;
use ResourceObjectMappers\CollectableTreasureMapper;
use Controllers\CollectableTreasureResourceController;
use ResourceObjectMappers\ObjectToResourcePropertiesEncoder;
use ResourceObjectMappers\PlayerMapper;
use Resources\CollectableTreasureResource;
use Resources\PlayerResource;
use ResourceObjectMappers\TeamMapper;
use Resources\TeamResource;
use Slim\Exception\NotFoundException;

use DbObjects\Team;
use DatabaseAccessObjects\TeamDAO;
use DatabaseAccessObjects\PlayerDAO;

use Controllers\PlayerResourceController;
use Authorisation\Permission;
use Resources\Resource;

require_once 'db_objects/Team.php';
require_once 'resources/TeamResource.php';
require_once 'resources/PlayerResource.php';
require_once 'database_access_objects/TeamDAO.php';
require_once 'database_access_objects/PlayerDAO.php';
require_once 'controllers/PlayerResourceController.php';
require_once 'controllers/CollectableTreasureResourceController.php';
require_once 'authorisation/Permission.php';
require_once 'authorisation/AuthUser.php';
require_once 'resource_object_mappers/TeamMapper.php';
require_once 'resource_object_mappers/PlayerMapper.php';
require_once 'resource_object_mappers/CollectableTreasureMapper.php';

/**
 * Description of TeamResourceController
 *
 * @author Edward Curran
 */
class TeamResourceController extends ResourceController{

    private $teamMapper;
    private $playerMapper;
    private $collectableTreasureMapper;

    private function getTeamId(){
        $this->mustBeFromDatabase();
        return $this->databaseResourceParams['id'];
    }

    function __construct(AuthUser $authUser, TeamResource $resource, $fromDatabase)
    {

        parent::__construct($authUser, $resource, $fromDatabase, ['id', 'current_treasure_index'], ['id']);

        $this->teamMapper = new TeamMapper();
        $this->playerMapper = new PlayerMapper();
        $this->collectableTreasureMapper = new CollectableTreasureMapper();

        $this->defineSubresource('players', function(){

            return $this->playerMapper->getPlayersCollection($this->getTeamId());


        }, function($playerResourceData, $fromDatabase){
            $childPlayerResourceData = $this->setChildParent($playerResourceData, 'team_id', $this->getTeamId());

            $playerResource = new PlayerResource($childPlayerResourceData);

            return new PlayerResourceController($this->authUser, $playerResource, $fromDatabase);

        });

        $this->defineSubresource('collectable_treasures', function(){

            return $this->collectableTreasureMapper->getTeamCollectableTreasures($this->getTeamId());


        }, function($collectableTreasureResourceData, $fromDatabase){
            $childCollectableTreasureResourceData =
                $this->setChildParent($collectableTreasureResourceData, 'team_id', $this->getTeamId());

            $collectableTreasureResource = new CollectableTreasureResource($childCollectableTreasureResourceData);

            return new CollectableTreasureResourceController($this->authUser, $collectableTreasureResource, $fromDatabase, $this->databaseResourceParams['current_treasure_index']);

        });


        $this->permissionsPolicy->addPermission((new Permission('team_access'))->withObtainPermissionFunction(
            function($teamId){
                if($this->authUser->isPlayer()) {
                    $authPlayer = $this->authUser->getUserObject();

                    return $authPlayer->team_id == $teamId;
                }

                return false;
            })->withReadAttributes(['id', 'name', 'current_treasure_index','score', 'public_team_code', 'max_players', 'active_treasure_hunt_id', 'players', 'collectable_treasures'])
        );

        $this->permissionsPolicy->addPermission((new Permission('admin_access'))->withObtainPermissionFunction(
            function($teamId){
                return $this->authUser->isAdmin();
            })->withAnyAttributes( ['id', 'current_treasure_index','score', 'name', 'public_team_code', 'max_players', 'active_treasure_hunt_id', 'players', 'collectable_treasures'])
        );

        $this->permissionsPolicy->addPermission((new Permission('organiser_active_hunt_access'))
            ->withAnyAttributes(['id', 'current_treasure_index', 'name', 'score', 'public_team_code', 'max_players', 'active_treasure_hunt_id', 'players', 'collectable_treasures']));

        $this->permissionsPolicy->addPermission((new Permission('hunt_access'))->withReadAttributes(['id', 'name', 'current_treasure_index','score', 'active_treasure_hunt_id']));

        $this->permissionsPolicy->addPermission((new Permission('base_access'))->withObtainPermissionFunction(
            function($teamId){
                return $this->authUser->isBaseUser();
            })->withCreateAttributes( ['players'])
        );

        $this->authorise();
    }

    function addPlayer($playerId){
        $this->mustBeFromDatabase();
        $playerResource = $this->playerMapper->getTeamPlayer($playerId, $this->getTeamId());
        return $this->addSubresource('players', $playerResource->getProperties(), true);
    }

    function addCollectableTreasure($treasureOrder){
        $this->mustBeFromDatabase();
        $collectableTreasureResource =
            $this->collectableTreasureMapper->getCollectableTreasureResource($this->getTeamId(), $treasureOrder);

        return $this->addSubresource('collectable_treasures', $collectableTreasureResource->getProperties(), true);
    }

    function addCurrentCollectableTreasure(){
        $this->mustBeFromDatabase();

        try {
            return $this->addCollectableTreasure($this->databaseResourceParams['current_treasure_index']);
        }catch(\ResourceNotFoundException $e){
            throw new \ForbiddenResourceException("team has no more treasures to collect");
        }
    }

    function getUri()
    {
        $url = "/teams/";
        return $url . $this->getTeamId();

    }

    protected  function storeResource(array $resourceProperties)
    {
        $createdResource = $this->teamMapper->storeResource((new TeamResource($resourceProperties)));

        //could set the resource of this controller to the created resource
        //need to make sure we then reauthorise and set fromDatabase and auth params
        return new TeamResourceController($this->authUser, $createdResource, true );
        // TODO: Implement storeResource() method.
    }
}



