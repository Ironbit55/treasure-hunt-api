<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 18/04/17
 * Time: 20:22
 */

namespace Controllers;


use ResourceObjectMappers\TreasureMapper;
use Resources\TreasureResource;
use DbObjects\Treasure;
use DatabaseAccessObjects\TreasureDAO;
use Authorisation\Permission;
use Authorisation\AuthUser;
use Resources\Resource;

require_once 'resource_object_mappers/TreasureMapper.php';
require_once 'resources/TreasureResource.php';
require_once 'db_objects/Treasure.php';
require_once 'database_access_objects/TreasureDAO.php';


require_once 'authorisation/Permission.php';
require_once 'authorisation/AuthUser.php';
require_once 'resources/Resource.php';


class TreasureResourceController extends ResourceController
{
    private $treasureMapper;

    public function getTreasureId(){
        $this->mustBeFromDatabase();
        return $this->databaseResourceParams['id'];
    }
    function __construct(AuthUser $authUser, $resource, $fromDatabase)
    {

        parent::__construct($authUser, $resource, $fromDatabase, ['id', 'qr_code'], ['id']);
        $this->treasureMapper = new TreasureMapper();

        $this->permissionsPolicy->addPermission((new Permission('admin_access'))->withObtainPermissionFunction(
            function($teamId){
                return $this->authUser->isAdmin();
            })->withAnyAttributes( ['id', 'clue', 'latitude', 'longitude', 'difficulty', 'default_order', 'qr_code', 'treasure_hunt_template_id'])
        );

        //current auth user owns the treasureHuntTemplate this treasure is attached to
        $this->permissionsPolicy->addPermission((new Permission('organiser_hunt_template_access'))->withAnyAttributes( ['id', 'clue', 'latitude', 'longitude', 'difficulty', 'default_order', 'qr_code', 'treasure_hunt_template_id']));


    }

    function getUri()
    {
        $url = "/treasures/";
        return $url . $this->getTreasureId();
    }

    function storeResource(array $resourceProperties)
    {
        // TODO: Implement storeResource() method.
        $createdTreasureResource = $this->treasureMapper->storeResource(new TreasureResource($resourceProperties));

        return new TreasureResourceController($this->authUser, $createdTreasureResource, true);
    }
}