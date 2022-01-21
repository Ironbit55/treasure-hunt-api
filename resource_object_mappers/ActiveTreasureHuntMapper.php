<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 15:10
 */

namespace ResourceObjectMappers;

use DatabaseAccessObjects\ActiveTreasureHuntDAO;
use DbObjects\ActiveTreasureHunt;
use Resources\ActiveTreasureHuntResource;
use Resources\Resource;
use ResourceObjectMappers\ObjectToResourcePropertiesEncoder;

require_once 'db_objects/ActiveTreasureHunt.php';
require_once 'database_access_objects/ActiveTreasureHuntDAO.php';
require_once 'resources/ActiveTreasureHuntResource.php';
require_once 'resources/Resource.php';
require_once 'resource_object_mappers/ObjectToResourcePropertiesEncoder.php';

/**
 * Class ActiveTreasureHuntMapper
 * @package ResourceObjectMappers
 *
 * defines a mapping between the properties of the ActiveTreasureHunt resource
 * and fields of ActiveTreasureHunt DbObject
 */
class ActiveTreasureHuntMapper{
    private $activeTreasureHuntDAO;
    private $activeTreasureHuntObjectEncoder;
    function __construct()
    {

        $this->activeTreasureHuntDAO = new ActiveTreasureHuntDAO();
        //define a mapping between the fields ActiveTreasureHunt DbObject and the activeTreasureHuntResource properties
        $resourcePropertyNames = ['id', 'name', 'is_started', 'start_time', 'is_finished', 'finish_time', 'organiser_id', 'treasure_hunt_template_id'];
        $objectFields = ['id', 'name', 'is_started', 'start_time', 'is_finished', 'finish_time', 'organiser_id', 'treasure_hunt_template_id'];
        $this->activeTreasureHuntObjectEncoder= new ObjectToResourcePropertiesEncoder($resourcePropertyNames, $objectFields);

    }

    public function resource_decode(ActiveTreasureHuntResource $activeTreasureHuntResource){
        $activeTreasureHuntProperties = $activeTreasureHuntResource->getProperties();

        return $this->activeTreasureHuntObjectEncoder->resource__properties_decode($activeTreasureHuntProperties);

    }

    public function resource_encode(ActiveTreasureHunt $activeTreasureHunt){
        if(!$activeTreasureHunt){
            return null;
        }

        $activeTreasureHuntResourceData = $this->activeTreasureHuntObjectEncoder->resource_properties_encode($activeTreasureHunt);
        return new ActiveTreasureHuntResource($activeTreasureHuntResourceData);
    }

    public function getCollection($organiserId){

    }

    //public function storeCollection($organiserId){}

    public function storeResource(Resource $activeTreasureHuntResource){
        $createdActiveTreasureHunt = $this->activeTreasureHuntDAO->createActiveTreasureHuntTemp($this->resource_decode($activeTreasureHuntResource));
        if(!$createdActiveTreasureHunt){
            return null;
        }
        return $this->resource_encode($createdActiveTreasureHunt);
    }

    public function getResource($activeTreasureHuntId){
        $activeTreasureHunt = $this->activeTreasureHuntDAO->getActiveTreasureHunt($activeTreasureHuntId);

        if(!$activeTreasureHunt){
            throw new \UnauthorisedResourceException("could not find active_treasure_hunt with id: \"$activeTreasureHuntId\" ");
        }

        return $this->resource_encode($activeTreasureHunt);
    }

    public function getOrganiserActiveTreasureHuntResourceById($activeTreasureHuntId, $organiserId){
        $activeTreasureHunt = $this->activeTreasureHuntDAO->getActiveTreasureHunt($activeTreasureHuntId);

        if(!$activeTreasureHunt || $activeTreasureHunt->organiser_id != $organiserId){
            throw new \UnauthorisedResourceException("could not find active_treasure_hunt with id: \"$activeTreasureHuntId\" ");
        }

        return $this->resource_encode($activeTreasureHunt);
    }

}