<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 18/04/17
 * Time: 19:42
 */

namespace Controllers;


use ResourceObjectMappers\TreasureHuntTemplateMapper;
use ResourceObjectMappers\TreasureMapper;
use Resources\TreasureHuntTemplateResource;
use DbObjects\TreasureHuntTemplate;
use DatabaseAccessObjects\TreasureHuntTemplateDAO;
use Authorisation\Permission;
use Authorisation\AuthUser;
use Resources\Resource;
use Resources\TreasureResource;

require_once 'resource_object_mappers/TreasureHuntTemplateMapper.php';
require_once 'resources/TreasureHuntTemplateResource.php';
require_once 'db_objects/TreasureHuntTemplate.php';
require_once 'database_access_objects/TreasureHuntTemplateDAO.php';

require_once 'database_access_objects/TreasureDAO.php';
require_once 'resources/TreasureResource.php';
require_once 'db_objects/Treasure.php';
require_once 'controllers/TreasureResourceController.php';

require_once 'resource_object_mappers/TreasureHuntTemplateMapper.php';

require_once 'authorisation/Permission.php';
require_once 'authorisation/AuthUser.php';
require_once 'resources/Resource.php';
require_once 'controllers/ResourceController.php';



class TreasureHuntTemplateResourceController extends ResourceController
{
    private $treasureHuntTemplateMapper;
    private $treasureMapper;
    public function getTreasureHuntTemplateId(){
        $this->mustBeFromDatabase();
        return $this->databaseResourceParams['id'];
    }
    function __construct(AuthUser $authUser, $resource, $fromDatabase)
    {
        parent::__construct($authUser, $resource, $fromDatabase, ['id'], ['organiser_id']);

        $this->treasureHuntTemplateMapper = new TreasureHuntTemplateMapper();
        $this->treasureMapper = new TreasureMapper();

        $this->defineSubresource('treasures',

            function () {
                return $this->treasureMapper->getCollection($this->getTreasureHuntTemplateId());
            },
            function ($treasureData, $fromDatabase) {
                $childTreasureData =
                    $this->setChildParent($treasureData, 'treasure_hunt_template_id', $this->getTreasureHuntTemplateId());

                $treasureResource = new TreasureResource($childTreasureData);

                return new TreasureResourceController($this->authUser, $treasureResource, $fromDatabase);
            }
        );
        //current auth user owns this treasure hunt
        $this->permissionsPolicy->addPermission((new Permission('organiser_hunt_template_access'))->withObtainPermissionFunction(
            function ($organiserId) {
                if ($this->authUser->isOrganiser()) {
                    $authOrganiser = $this->authUser->getUserObject();

                    return $organiserId == $authOrganiser->id;
                }
                return false;
            })->withAnyAttributes(['id', 'name', 'organiser_id', 'treasures'])
        );

        $this->permissionsPolicy->addPermission((new Permission('admin_access'))->withObtainPermissionFunction(
            function(){
                return $this->authUser->isAdmin();
            })->withAnyAttributes( ['id', 'name','organiser_id', 'treasures'])
        );

        $this->permissionsPolicy->addPermission((new Permission('organiser_access'))
            ->withCreateAttributes( ['id', 'name','organiser_id', 'treasures'])
        );

        $this->authorise();
    }

    function getUri()
    {
        $url = "/treasure_hunt_templates/";
        return $url . $this->getTreasureHuntTemplateId();

    }

    function storeResource(array $resourceProperties)
    {

        $treasureHuntTemplateResource = new TreasureHuntTemplateResource($resourceProperties);

        $createdTreasureHuntTemplateResource = $this->treasureHuntTemplateMapper->storeResource($treasureHuntTemplateResource);


        return new TreasureHuntTemplateResourceController($this->authUser, $createdTreasureHuntTemplateResource, true);
    }

    function addTreasureById($treasureId){
        $treasureResource = $this->treasureMapper->getResource($treasureId);

        return $this->addSubresource('treasures', $treasureResource->getProperties(), true);
    }
}