<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 19/03/2017
 * Time: 17:09
 */

namespace Controllers;

use DbObjects\Player;
use DatabaseAccessObjects\PlayerDAO;
use Authorisation\AuthUser;
use Authorisation\Permission;
use ResourceObjectMappers\PlayerMapper;
use Resources\PlayerResource;
use Authorisation\AuthUserType;


require_once 'db_objects/Player.php';
require_once 'database_access_objects/PlayerDAO.php';
require_once 'authorisation/AuthUser.php';
require_once 'authorisation/Permission.php';


/**
 * handles interacting with the player resource, creating new players, 
 * getting representations etc. 
 */

class PlayerResourceController extends ResourceController{
    private $playerMapper;

    public function getPlayerId(){
        $this->mustBeFromDatabase();
        return $this->databaseResourceParams['id'];
    }
    function __construct(AuthUser $authUser, PlayerResource $resource, $fromDatabase)
    {
        parent::__construct($authUser, $resource, $fromDatabase, ['id', 'token'], ['id']);
        $this->playerMapper = new PlayerMapper();


        $this->permissionsPolicy->addPermission((new Permission('player_access'))->withObtainPermissionFunction(
            function($playerId){
                //players have full access to their own player resource

                if(!$this->authUser->isPlayer()){
                    return false;
                }

                $authPlayer = $this->authUser->getUserObject();
                return $authPlayer->id == $playerId;
            })->withReadAttributes(['id', 'name','team_id', 'token'])
        );

        $this->permissionsPolicy->addPermission((new Permission('admin_access'))->withObtainPermissionFunction(
            function($playerId){
                //admin has some access to all players
                return $this->authUser->isAdmin();
            })->withAnyAttributes(['id', 'name','team_id'])
        );

        $this->permissionsPolicy->addPermission((new Permission('team_access'))->withReadAttributes(['id', 'name']));

        //$this->permissionsPolicy->addPermission((new Permission('hunt_access'))->withReadAttributes(['name']));

        $this->permissionsPolicy->addPermission((new Permission('organiser_active_hunt_access'))->withReadAttributes(['id', 'name']));

        $this->permissionsPolicy->addPermission((new Permission('base_access'))->withCreateAttributes(['name', 'team_id']));


        //check user is authorised to access this resource
        $this->authorise();
    }

    function storeResource(array $resourceProperties)
    {
        $playerResource = new PlayerResource($resourceProperties);
        $createdPlayerResource = $this->playerMapper->storeResource($playerResource);

        //kind of super hacky but also kinda makes sense, current user just created a player resource
        //so we can say it becomes authorised as the player it just created
        $authUser = new AuthUser(AuthUserType::Player,
            $this->playerMapper->resource_decode($createdPlayerResource));

        return new PlayerResourceController($authUser, $createdPlayerResource, true);
    }
    function getUri()
    {
        // TODO: Implement getUri() method.
        $url = "/players/";
        return $url . $this->getPlayerId();

    }
}