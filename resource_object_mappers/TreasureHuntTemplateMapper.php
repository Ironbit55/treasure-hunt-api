<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 18/04/17
 * Time: 19:16
 */

namespace ResourceObjectMappers;


use DatabaseAccessObjects\TreasureHuntTemplateDAO;
use DbObjects\TreasureHuntTemplate;
use Resources\TreasureHuntTemplateResource;
use Resources\Resource;
use ResourceObjectMappers\ObjectToResourcePropertiesEncoder;

require_once 'db_objects/TreasureHuntTemplate.php';
require_once 'database_access_objects/TreasureHuntTemplateDAO.php';
require_once 'resources/TreasureHuntTemplateResource.php';
require_once 'resources/Resource.php';
require_once 'resource_object_mappers/ObjectToResourcePropertiesEncoder.php';


class TreasureHuntTemplateMapper
{
    private $treasureHuntTemplateObjectEncoder;
    private $treasureHuntTemplateDAO;
    function __construct()
    {
        $resourcePropertyNames = ['id', 'name', 'organiser_id'];
        $objectFields = ['id', 'name', 'organiser_id'];
        $this->treasureHuntTemplateObjectEncoder= new ObjectToResourcePropertiesEncoder($resourcePropertyNames, $objectFields);
        $this->treasureHuntTemplateDAO = new TreasureHuntTemplateDAO();
    }

    public function resource_decode(TreasureHuntTemplateResource $treasureHuntTemplateResource){
        $resourceData = $treasureHuntTemplateResource->getProperties();

        return $this->treasureHuntTemplateObjectEncoder->resource__properties_decode($resourceData);
    }

    public function resource_encode(TreasureHuntTemplate $treasureHuntTemplate){

        $treasureHuntResourceData = $this->treasureHuntTemplateObjectEncoder->resource_properties_encode($treasureHuntTemplate);
        return new TreasureHuntTemplateResource($treasureHuntResourceData);
    }

    public function storeResource(TreasureHuntTemplateResource $treasureHuntTemplateResource){
        $treasureHuntTemplate = $this->resource_decode($treasureHuntTemplateResource);

        $createdTreasureHunt = $this->treasureHuntTemplateDAO->createTreasureHuntTemplate($treasureHuntTemplate);
        if(!$createdTreasureHunt){
            return null;
        }
        return $this->resource_encode($createdTreasureHunt);

    }

    public function getCollectionFromDatabase($organiserId){
        $treasureHuntTemplates = $this->treasureHuntTemplateDAO->getAllTreasureHuntTemplatesBelongingToOrganiser($organiserId);
        $treasureHuntTemplateCollectionData = array();
        foreach($treasureHuntTemplates as $treasureHuntTemplate){
            $treasureHuntTemplateResource = $this->resource_encode($treasureHuntTemplate);
            $treasureHuntTemplateCollectionData[] = $treasureHuntTemplateResource->getProperties();
        }
        return $treasureHuntTemplateCollectionData;
    }

    public function getTreasureHuntTemplate($treasureHuntTemplateId){
        $treasureHuntTemplate = $this->treasureHuntTemplateDAO->getTreasureTemplate($treasureHuntTemplateId);
        if(!$treasureHuntTemplate){
            throw new \ResourceNotFoundException("could not find treasure_hunt_template with id: \"$treasureHuntTemplateId\" ");
        }
        return $this->resource_encode($treasureHuntTemplate);
    }
}