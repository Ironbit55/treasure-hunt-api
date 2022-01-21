<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 20/04/17
 * Time: 10:09
 */

namespace ResourceObjectMappers;

use DbObjects\Player;
use Resources\PlayerResource;
use DatabaseAccessObjects\PlayerDAO;


require_once 'resource_object_mappers/ObjectToResourcePropertiesEncoder.php';
require_once 'db_objects/Player.php';
require_once 'resources/PlayerResource.php';
require_once 'database_access_objects/PlayerDAO.php';
require_once 'controllers/TeamResourceController.php';

class PlayerMapper
{
    private $playerObjectEncoder;
    private $playerDAO;
    function __construct()
    {
        $resourcePropertyName = ['id', 'name','team_id', 'token'];
        $resourceFieldNames = ['id', 'name','team_id', 'token'];
        $this->playerObjectEncoder = new ObjectToResourcePropertiesEncoder($resourcePropertyName, $resourceFieldNames);
        $this->playerDAO = new PlayerDAO();
    }

    function resource_encode(Player $player){
        $playerResourceProperties = $this->playerObjectEncoder->resource_properties_encode($player);
        return new PlayerResource($playerResourceProperties);
    }

    function resource_decode(PlayerResource $playerResource){
        return $this->playerObjectEncoder->resource__properties_decode($playerResource->getProperties());
    }

    function storeResource(PlayerResource $playerResource){
        $player = $this->resource_decode($playerResource);
        $createdPlayer = $this->playerDAO->createPlayerFromObject($player);
        if(!$createdPlayer){
            return null;
        }
        return $this->resource_encode($createdPlayer);
    }

    function getPlayer($playerId){
        $player = $this->playerDAO->getPlayerById($playerId);
        if(!$player){
            throw new \ResourceNotFoundException("could not find player resource with id: \"$playerId\"");
        }
        return $this->resource_encode($player);
    }

    function getTeamPlayer($playerId, $teamId){
        $player = $this->playerDAO->getPlayerByTeamIdAndId($playerId, $teamId);
        if(!$player){
            throw new \ResourceNotFoundException("could not find player resource with id: \"$playerId\"");
        }
        return $this->resource_encode($player);
    }

    function getPlayersCollection($teamId){
        $players = $this->playerDAO->getAllPlayersInTeam($teamId);
        $playerCollectionData = [];
        foreach($players as $player){
            $playerResource = $this->resource_encode($player);
            $playerCollectionData[] = $playerResource->getProperties();
        }
        return $playerCollectionData;
    }
}