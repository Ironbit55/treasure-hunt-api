<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 11/04/17
 * Time: 14:13
 */

namespace Controllers;

use Authorisation\AuthUser;
use Authorisation\AuthUserType;
use ResourceObjectMappers\TreasureHuntTemplateMapper;
use Resources\ActiveTreasureHuntResource;
use Resources\TreasureHuntTemplateResource;
use Slim\Exception\MethodNotAllowedException;
use Resources\Resource;
use Resources\OrganiserResource;

use DatabaseAccessObjects\OrganiserDAO;
use ResourceObjectMappers\OrganiserMapper;

use ResourceObjectMappers\ActiveTreasureHuntMapper;
use DatabaseAccessObjects\ActiveTreasureHuntDAO;
use Authorisation\Permission;



require_once 'authorisation/Permission.php';
require_once 'authorisation/AuthUser.php';

require_once 'controllers/ResourceController.php';
require_once 'controllers/ActiveTreasureHuntRC.php';
require_once 'controllers/TreasureHuntTemplateResourceController.php';

require_once 'resources/OrganiserResource.php';
require_once 'resources/ActiveTreasureHuntResource.php';
require_once 'resources/TreasureHuntTemplateResource.php';

require_once 'db_objects/Organiser.php';

require_once  'resource_object_mappers/OrganiserMapper.php';
require_once  'resource_object_mappers/ActiveTreasureHuntMapper.php';
require_once  'resource_object_mappers/TreasureHuntTemplateMapper.php';

require_once 'database_access_objects/ActiveTreasureHuntDAO.php';
require_once 'database_access_objects/OrganiserDAO.php';

class OrganiserResourceController extends ResourceController
{

    private $organiserResourceMapper;
    private $activeTreasureHuntMapper;
    private $treasureHuntTemplateMapper;
    private $activeTreasureHuntDAO;

    function parseRequestBodyToResource(){

    }

    function getOrganiserId(){
        $this->mustBeFromDatabase();
        return $this->databaseResourceParams['id'];
    }

    function addActiveTreasureHuntSubresource(Resource $resource){

    }

    function __construct($authUser, Resource $resource, $fromDatabase){

        $this->organiserResourceMapper = new OrganiserMapper();
        $this->activeTreasureHuntDAO = new ActiveTreasureHuntDAO();
        $this->activeTreasureHuntMapper = new ActiveTreasureHuntMapper();
        $this->treasureHuntTemplateMapper = new TreasureHuntTemplateMapper();

        parent::__construct($authUser, $resource, $fromDatabase, array('id'), array('id'));

        $this->setSubresourceNesting(false);

        $this->defineSubresource('active_treasure_hunts',

            function () {
                $activeTreasureHunts = $this->activeTreasureHuntDAO->getAllActiveTreasureHuntsBelongingToOrganiser($this->getOrganiserId());
                $activeTreasureHuntsData = array();

                foreach ($activeTreasureHunts as $activeTreasureHuntData) {
                    $activeTreasureHuntResource = $this->activeTreasureHuntMapper->resource_encode($activeTreasureHuntData);
                    $activeTreasureHuntsData[] = $activeTreasureHuntResource->getProperties();
                }
                return $activeTreasureHuntsData;
            },
            function ($activeTreasureHuntResourceData, $fromDatabase) {

                $childActiveTreasureHuntData =
                    $this->setChildParent($activeTreasureHuntResourceData, 'organiser_id', $this->getOrganiserId());

                $activeTreasureHuntResource = new ActiveTreasureHuntResource($childActiveTreasureHuntData);


                return new ActiveTreasureHuntRC($this->authUser, $activeTreasureHuntResource, $fromDatabase);
            }
        );

        $this->defineSubresource('treasure_hunt_templates',
            function () {
                return $this->treasureHuntTemplateMapper->getCollectionFromDatabase($this->getOrganiserId());
            },

            function ($treasureHuntTemplateData, $fromDatabase) {
                try {
                    $childTreasureHuntTemplateData =
                        $this->setChildParent($treasureHuntTemplateData, 'organiser_id', $this->getOrganiserId());
                }catch (\ResourceNotFoundException $e){
                    throw new \ResourceNotFoundException("could not find treasure hunt template resource with id: \"" . $treasureHuntTemplateData['id'] . "\"");
                }

                $treasureHuntTemplateResource = new TreasureHuntTemplateResource($childTreasureHuntTemplateData);



                return new TreasureHuntTemplateResourceController($this->authUser, $treasureHuntTemplateResource, $fromDatabase);
            }
        );


        $this->permissionsPolicy->addPermission((new Permission('organiser_access'))->withObtainPermissionFunction(
            function($organiserId){
                if($this->authUser->isOrganiser()){
                    return $this->authUser->getUserObject()->id == $organiserId;
                }
                return false;
            })->withAnyAttributes( ['id', 'first_name', 'last_name', 'username', 'active_treasure_hunts', 'treasure_hunt_templates', 'token'])
        );

        $this->permissionsPolicy->addPermission((new Permission('admin_access'))->withObtainPermissionFunction(
            function($organiserId){
                return $this->authUser->isAdmin();
            })->withReadAttributes( ['id', 'first_name', 'last_name', 'username', 'active_treasure_hunts', 'treasure_hunt_templates', 'token'])
            ->withCreateAttributes(['first_name', 'last_name', 'username', 'password'])
        );

        $this->permissionsPolicy->addPermission((new Permission('base_access'))
            ->withCreateAttributes(['first_name', 'last_name', 'username', 'password'])
        );




    }

    function addActiveTreasureHuntById($activeTreasureHuntId){
        $activeTreasureHuntResource = $this->activeTreasureHuntMapper->getOrganiserActiveTreasureHuntResourceById($activeTreasureHuntId, $this->getOrganiserId());

        return $this->addSubresource('active_treasure_hunts', $activeTreasureHuntResource->getProperties(), true );

    }

    function addTreasureHuntTemplateById($treasureHuntTemplateId){
        $treasureHuntTemplateResource = $this->treasureHuntTemplateMapper->getTreasureHuntTemplate($treasureHuntTemplateId);
        $treasureHuntTemplateController = $this->addSubresource('treasure_hunt_templates',
            $treasureHuntTemplateResource->getProperties(), true );


        return $treasureHuntTemplateController;

    }


    public function getActiveTreasureHuntsCollection(){
        return $this->getSubresourceCollectionRepresentation('active_treasure_hunts');
    }

    function getUri()
    {
        $url = "/organisers/";
        return $url . $this->getOrganiserId();
        // TODO: Implement getUri() method.
    }

    function storeResource(array $organiserResourceProperties)
    {
        $password = $organiserResourceProperties['password'];

        $username = $organiserResourceProperties['username'];

        if(!preg_match("/^[a-zA-Z0-9_-]{3,16}$/", $username)){
            throw new \UnprocessableResourceException("value for property: \"username\" is not valid");
        }

        //this isn't the place to do this but i'm having trouble because the validation
        //for password only makes sense before it has been hashed

        //username must be between 6 and 18 characters
        //accepts, alphanumeric characters + "-" and "_"
        if(!preg_match("/^[a-zA-Z0-9_-]{6,18}$/", $password)){
            throw new \UnprocessableResourceException("value for property: \"password\" is not valid");
        }

        $organiserResourceProperties['password'] = password_hash($password, PASSWORD_DEFAULT);

        $organiserResource = new OrganiserResource($organiserResourceProperties);


        $createdResource = $this->organiserResourceMapper->saveOrganiserResourceToDatabase($organiserResource);

        //kind of super hacky but also kinda makes sense, current user just created an organiser resource
        //aka it became an organiser, so we can set its authUser to be the organiser it just created
        $authUser = new AuthUser(AuthUserType::Organiser,
            $this->organiserResourceMapper->resourceDecode($createdResource));

        return new OrganiserResourceController($authUser, $createdResource, true);

    }

    function login(){
        if(!$this->fromDatabase) {

            $candidateUsername = $this->resource->getProperty('username');
            $candidatePassword = $this->resource->getProperty('password');


            $databaseOrganiser = $this->organiserResourceMapper->getOrganiserResourceByUsername($candidateUsername);
            $hashedPassword = $databaseOrganiser->getProperty('password');


            $organiserDAO = new OrganiserDAO();
            $token = $organiserDAO->getToken($candidatePassword, $hashedPassword);

            if (!$token) {
                throw new \UnauthorisedResourceException("incorrect password");
            }
            $organiserDAO->updateToken($candidateUsername, $token);

            $databaseOrganiser->setProperty('token', $token);

            $authUser = new AuthUser(AuthUserType::Organiser,
                $this->organiserResourceMapper->resourceDecode($databaseOrganiser));

            return new OrganiserResourceController($authUser,  $databaseOrganiser, true);
        }
        throw new \BadMethodCallException("can't login organiser already from database");
    }

}