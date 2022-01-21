<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 15:11
 */

namespace ResourceObjectMappers;

use DbObjects\Organiser;
use DatabaseAccessObjects\OrganiserDAO;
use Resources\OrganiserResource;
use ResourceObjectMappers\ObjectToResourcePropertiesEncoder;

require_once 'db_objects/Organiser.php';
require_once 'database_access_objects/OrganiserDAO.php';
require_once 'resources/OrganiserResource.php';


class OrganiserMapper{
    private $organiserDAO;
    private $organiserObjectEncoder;

    function __construct()
    {
        $this->organiserDAO = new OrganiserDAO();
        $resourcePropertyNames = ['id', 'first_name', 'last_name', 'username', 'active_treasure_hunts', 'password', 'token'];
        $objectFields = ['id', 'first_name', 'last_name', 'username', 'active_treasure_hunts', 'password', 'token'];
        $this->organiserObjectEncoder = new ObjectToResourcePropertiesEncoder($resourcePropertyNames, $objectFields);
    }

    function getOrganiserResourceById($organiserId){
        $organiserDAO = new OrganiserDAO();
        $organiser = $organiserDAO->getOrganiserById($organiserId);
        if(!$organiser){
            throw new \ResourceNotFoundException("could not find organiser resource with id: \"$organiserId\"");
        }
        return $this->resourceEncode($organiser);
    }
    function getOrganiserResourceByUsername($username){
        $organiserDAO = new OrganiserDAO();
        $organiser = $organiserDAO->getOrganiserByUsername($username);
        if(!$organiser){
            throw new \ResourceNotFoundException("could not find organiser resource with username: \"$username\"");
        }
        return $this->resourceEncode($organiser);
    }
    //converts organiser database representation to resource
    function resourceEncode(Organiser $organiser){
        if(!$organiser){
            return null;
        }

        $organiserResourceData = $this->organiserObjectEncoder->resource_properties_encode($organiser);
        return new OrganiserResource($organiserResourceData);
    }

    //converts organiser resource to its database representation
    function resourceDecode(OrganiserResource $organiserResource){
        $organiser = $this->organiserObjectEncoder->resource__properties_decode($organiserResource->getProperties());
        return $organiser;
    }



    function saveOrganiserResourceToDatabase(OrganiserResource $organiserResource){
        $organiser = $this->resourceDecode($organiserResource);
        $organiserDAO = new OrganiserDAO();
        try {
            $organiser = $organiserDAO->createOrganiser($organiser);
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == MYSQL_CODE_DUPLICATE_KEY) {
                //The INSERT query failed due to a key constraint violation.
                //aka our public team code was not unique
                throw new \DuplicateResourceException("username is already in use");
            } else {
                //its a different type of PDO exception which we still want to throw
                throw $e;
            }
        }

        if(!$organiser){
            return null;
        }

        return $this->resourceEncode($organiser);
    }

    private function loadOrganiserResourceFromDatabase($organiserId){
        $organiserDAO = new OrganiserDAO();
        $organiser = $organiserDAO->getOrganiserById($organiserId);
        if(!$organiser){
            return null;
        }
        return $this->resourceEncode($organiser);
    }

    public function getCollection(){

        $organisers = $this->organiserDAO->getAllOrganisers();

        $organiserResources = array();
        $orgainserResourceControllers = array();
        foreach($organisers as $organiser){
            $organiserResources[] = $this->resourceEncode($organiser);
        }
        if(!$organiserResources){
            return null;
        }
        return $organiserResources;
    }
}