<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 12/04/17
 * Time: 16:58
 */

namespace Controllers;

use Authorisation\AuthUser;
use Controllers\ResourceController;
use Controllers\OrganiserResourceController;
use ResourceObjectMappers\OrganiserMapper;
use Resources\BaseResource;
use Authorisation\Permission;
use Resources\OrganiserResource;
use Resources\Resource;


require_once 'controllers/ResourceController.php';
require_once 'controllers/OrganiserResourceController.php';
require_once 'resources/BaseResource.php';
require_once 'resources/Resource.php';
require_once 'resource_object_mappers/OrganiserMapper.php';
require_once 'authorisation/Permission.php';


class BaseResourceController extends ResourceController
{
    private $organiserResourceMapper;

    function __construct(AuthUser $authUser)
    {
        $this->organiserResourceMapper = new OrganiserMapper();

        $resource = new BaseResource([]);

        parent::__construct($authUser, $resource, true, [], []);

        $this->permissionsPolicy->addPermission((new Permission('base_access'))->withObtainPermissionFunction(
            function(){
                return $this->authUser->isBaseUser();
            })->withCreateAttributes( ['organisers'])
        );

        $this->permissionsPolicy->addPermission((new Permission('admin_access'))->withObtainPermissionFunction(
            function(){
                return $this->authUser->isAdmin();
            })->withReadAttributes(['organisers'])
        );

        $this->defineSubresource('organisers',
            function() {
                $organiserResourceCollection = array();

                foreach ($this->organiserResourceMapper->getCollection() as $organiserResource) {

                    $organiserResourceCollection[] = $organiserResource->getProperties();

                }
                return $organiserResourceCollection;
            },

            function($organiserData, $fromDatabase) {

                $organiserResource = new OrganiserResource($organiserData);

                return new OrganiserResourceController($this->authUser, $organiserResource, $fromDatabase);
            }
        );

        $this->authorise();
    }

    function getOrganiser($organiserId){
        $organiserResource = $this->organiserResourceMapper->getOrganiserResourceById($organiserId);

        return $this->addSubresource('organisers', $organiserResource->getProperties(), true);

    }

    function getUri()
    {
        $url = "/";
        return $url;
    }


    function storeResource(array $resourceProperties)
    {
        // TODO: Implement storeResource() method.
        throw new \BadFunctionCallException("can't store base resource");
    }
}