<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 18/04/17
 * Time: 19:36
 */

namespace ResourceObjectMappers;


use DatabaseAccessObjects\TreasureDAO;
use DbObjects\Treasure;
use Resources\TreasureResource;

require_once 'db_objects/Treasure.php';
require_once 'database_access_objects/TreasureDAO.php';
require_once 'resources/TreasureResource.php';
require_once 'resources/Resource.php';
require_once 'resource_object_mappers/ObjectToResourcePropertiesEncoder.php';

class TreasureMapper
{
    private $treasureObjectEncoder;
    private $treasureDAO;
    function __construct()
    {
        $resourcePropertyNames = ['id', 'clue', 'latitude', 'longitude', 'difficulty', 'default_order', 'qr_code', 'treasure_hunt_template_id'];
        $objectFields = ['id', 'clue', 'latitude', 'longitude', 'difficulty', 'default_order', 'qr_code', 'treasure_hunt_template_id'];
        $this->treasureObjectEncoder= new ObjectToResourcePropertiesEncoder($resourcePropertyNames, $objectFields);
        $this->treasureDAO = new TreasureDAO();
    }

    public function resource_decode(TreasureResource $treasureResource){
        $resourceData = $treasureResource->getProperties();

        return $this->treasureObjectEncoder->resource__properties_decode($resourceData);
    }

    public function resource_encode(Treasure $treasure){

        $treasureResourceData = $this->treasureObjectEncoder->resource_properties_encode($treasure);
        return new TreasureResource($treasureResourceData);
    }

    public function storeResource(TreasureResource $treasureResource){
        $treasure = $this->resource_decode($treasureResource);
        $createdTreasure = null;

        try{
            $createdTreasure = $this->treasureDAO->createTreasure($treasure);
        }
        catch (\PDOException  $e) {
            if ($e->errorInfo[1] == MYSQL_CODE_DUPLICATE_KEY) {
                //The INSERT query failed due to a key constraint violation.
                //a treasure with the same default order value already exists
                throw new \DuplicateResourceException("treasure with default order: \"" .  $treasureResource->getProperty("default_order") . "\" already exists" );
            } else {
                //its a different type of PDO exception which we still want to throw
                throw $e;
            }
        }



        return $this->resource_encode($createdTreasure);
    }

    public function getTemplateTreasure($treasureId, $treasureHuntTemplateId){

    }

    public function getCollection($treasureHuntTemplateId){
        $treasureHunts = $this->treasureDAO->getTreasures($treasureHuntTemplateId);
        $treasureHuntCollectionData = array();
        foreach($treasureHunts as $treasureHunt){
            $treasureHuntResource = $this->resource_encode($treasureHunt);
            $treasureHuntCollectionData[] = $treasureHuntResource->getProperties();
        }

        return $treasureHuntCollectionData;

    }

    public function getResource($treasureId){
        $treasure = $this->treasureDAO->getTreasure($treasureId);
        if(!$treasure){
            throw new \ResourceNotFoundException("could not find treasure resource with id: \"$treasureId\"");
        }

        return $this->resource_encode($treasure);
    }

    public function getTreasureByOrderInHuntTemplate($treasureHuntTemplateId, $defaultOrder){

    }
}