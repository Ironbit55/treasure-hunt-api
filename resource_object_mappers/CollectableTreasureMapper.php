<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 20/04/17
 * Time: 12:57
 */

namespace ResourceObjectMappers;

use DatabaseAccessObjects\TeamTreasureDAO;
use DatabaseAccessObjects\TreasureDAO;
use DbObjects\TeamTreasure;
use DbObjects\Treasure;
use Resources\CollectableTreasureResource;

require_once 'resource_object_mappers/ObjectToResourcePropertiesEncoder.php';
require_once 'db_objects/Treasure.php';
require_once 'db_objects/TeamTreasure.php';
require_once 'resources/CollectableTreasureResource.php';
require_once 'database_access_objects/TreasureDAO.php';
require_once 'database_access_objects/TeamTreasureDAO.php';

class CollectableTreasureMapper
{
    private $treasureMapper;
    private $teamTreasureMapper;
    private $treasureDAO;
    private $teamTreasureDAO;

    function __construct()
    {
        $treasureResourcePropertyNames = ['clue', 'latitude','longitude', 'qr_code'];
        $treasureObjectFieldNames = ['clue', 'latitude', 'longitude', 'qr_code'];

        $teamTreasureResourcePropertyNames = ['team_id', 'treasure_hunt_template_id', 'has_been_found', 'order', 'score', 'found_time'];
        $teamTreasureObjectFieldName = ['team_id', 'treasure_hunt_template_id', 'has_been_found', 'order', 'score', 'found_time'];
        $this->treasureMapper = new ObjectToResourcePropertiesEncoder($treasureResourcePropertyNames, $treasureObjectFieldNames);
        $this->teamTreasureMapper = new ObjectToResourcePropertiesEncoder($teamTreasureResourcePropertyNames, $teamTreasureObjectFieldName);

        $this->treasureDAO = new TreasureDAO();
        $this->teamTreasureDAO = new TeamTreasureDAO();
    }

    function resource_encode(Treasure $treasure, TeamTreasure $teamTreasure){
        $treasureProperties = $this->treasureMapper->resource_properties_encode($treasure);
        $teamTreasureProperties = $this->teamTreasureMapper->resource_properties_encode($teamTreasure);

        $resourceProperties = array_merge($treasureProperties, $teamTreasureProperties);



        return new CollectableTreasureResource($resourceProperties);

    }

    function resource_decode(CollectableTreasureResource $collectableTreasureResource){
        $resourceProperties = $collectableTreasureResource->getProperties();

        $treasure = $this->treasureMapper->resource__properties_decode($resourceProperties);
        $teamTreasure = $this->teamTreasureMapper->resource__properties_decode($resourceProperties);

        return array("treasure" => $treasure, "team_treasure" => $teamTreasure);
    }

    function getTeamCollectableTreasures($teamId){
        $teamTreasures = $this->teamTreasureDAO->getTeamTreasures($teamId);
        $treasures = $this->treasureDAO->getTeamTreasureTreasures($teamId);
        if(count($teamTreasures) != count($treasures)){
            throw new \Exception("number of teamTreasures and Treasures for a team was different");
        }
        $collectableTreasureResourceCollectionData = [];
        foreach($treasures as $index => $treasure){
            $collectableTreasureResource = $this->resource_encode($treasures[$index], $teamTreasures[$index]);
            $collectableTreasureResourceCollectionData[] = $collectableTreasureResource->getProperties();

        }
       return $collectableTreasureResourceCollectionData;
    }

    public function getCollectableTreasureResource($teamId, $order){

        $teamTreasure = $this->teamTreasureDAO->getTeamTreasure($teamId, $order);
        $treasure = $this->treasureDAO->getTreasure($teamTreasure->treasure_id);

        if(!$teamTreasure || !$treasure){
            throw new \ResourceNotFoundException("could not find collectable treasure of order: \"$order\"");
        }

        return $this->resource_encode($treasure, $teamTreasure);
    }

}