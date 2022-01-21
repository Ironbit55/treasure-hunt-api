<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 17:20
 */

namespace ResourceObjectMappers;

use DatabaseAccessObjects\TeamDAO;
use DbObjects\Team;
use ResourceObjectMappers\ObjectToResourcePropertiesEncoder;
use Resources\TeamResource;
use Controllers\TeamResourceController;

require_once 'resource_object_mappers/ObjectToResourcePropertiesEncoder.php';
require_once 'db_objects/Team.php';
require_once 'resources/TeamResource.php';
require_once 'database_access_objects/TeamDAO.php';
require_once 'controllers/TeamResourceController.php';

class TeamMapper{
    private $treasureObjectEncoder;
    private $teamDAO;
    function __construct()
    {
        $resourcePropertyName = ['id', 'current_treasure_index', 'name', 'score', 'public_team_code', 'max_players', 'active_treasure_hunt_id'];
        $objectFieldNames = ['id', 'current_treasure_index', 'name', 'score', 'public_team_code', 'max_players', 'active_treasure_hunt_id'];
        $this->treasureObjectEncoder = new ObjectToResourcePropertiesEncoder($resourcePropertyName, $objectFieldNames);
        $this->teamDAO = new TeamDAO();
    }

    function resource_encode(Team $team){
        $teamResourceProperties = $this->treasureObjectEncoder->resource_properties_encode($team);
        return new TeamResource($teamResourceProperties);
    }

    function resource_decode(TeamResource $teamResource){
        return $this->treasureObjectEncoder->resource__properties_decode($teamResource->getProperties());
    }

    function storeResource(TeamResource $teamResource){

        $team = $this->teamDAO->createTeamTemp($this->resource_decode($teamResource));
        //if team is null here it means a new team wasn't created properly
        if (!$team) {
            return null;
        }

        return $this->resource_encode($team);
    }
    function getCollection($activeTreasureHuntId){
        $teamDAO = new TeamDAO();
        $teams = $teamDAO->getAllTeamsInActiveTreasureHunt($activeTreasureHuntId);

        $teamCollection = [];
        $teamCollectionData = [];
        foreach ($teams as $team) {
            $teamResource = $this->resource_encode($team);
            $teamCollectionData[] = $teamResource->getProperties();
            $teamCollection[] = $teamResource;
        }
        return $teamCollectionData;
    }



    function getResource($teamId, $activeTreasureHuntId){
        //make sure team belongs to this active treasure hunt

        $team = $this->teamDAO->getTeamByActiveTreasureHuntIdAndId($teamId, $activeTreasureHuntId);

        if (!$team) {
            //team with that id does not exist within specified active hunt
            throw new \ResourceNotFoundException("could not find team resource with id: \"$teamId\"");
        }

        return $this->resource_encode($team);
    }

    function getResourceByTeamId($teamId){

        $team = $this->teamDAO->getTeam($teamId);


        if (!$team) {
            //team with that id does not exist within specified active hunt
            throw new \ResourceNotFoundException("could not find team resource with id: \"$teamId\"");
        }

        return $this->resource_encode($team);
    }

    function getResourceByPublicTeamCode($teamCode){
        $team = $this->teamDAO->getTeamByPublicTeamCode($teamCode);
        if(!$team){
            throw new \ResourceNotFoundException("could not find team resource with code: \"$teamCode\"");
        }
        return $this->resource_encode($team);
    }
}