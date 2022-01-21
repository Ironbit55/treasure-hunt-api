<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DatabaseAccessObjects;

use DbObjects\Team;

require_once 'database_access_objects/DatabaseAccessObject.php';
require_once 'db_objects/Team.php';
/**
 * Description of TeamDAO
 *
 * @author Edward Currran
 */
define("MYSQL_CODE_DUPLICATE_KEY", 1062);
class TeamDAO extends DatabaseAccessObject {

    public function createTeam($activeTreasureHuntId, $maxPlayers){
        $defaultScore = 0;
        $defaultCurrentTreasureIndex = 0;

        //is set later
        $publicTeamCode = null;
        
        $sql = "INSERT INTO team (current_treasure_index, score, max_players, public_team_code, active_treasure_hunt_id)
                VALUES (:current_treasure_index, :score, :max_players, :public_team_code, :active_treasure_hunt_id )";
            
        $params = [':current_treasure_index' => $defaultCurrentTreasureIndex, ':score' => $defaultScore, ':max_players' => $maxPlayers,
            ':public_team_code' => $publicTeamCode, ':active_treasure_hunt_id' => $activeTreasureHuntId];


        //prepare the statement seperately as we might want to run it a few times
        //with different values
        $stmt = $this->connection->prepare($sql);

        //will execute our insert until we don't break the unique constraint
        //or we run out of attempts
        $publicTeamCodeUnique = false;
        $numberOfAttempts = 100;
        while((!$publicTeamCodeUnique && $numberOfAttempts > 0)) {
            $params[':public_team_code'] = $this->getPublicTeamCode();
            try {
                //assume its unique unless it fails
                $publicTeamCodeUnique = true;
                $numberOfAttempts--;
                $stmt->execute($params);
            } catch (\PDOException $e) {
                if ($e->errorInfo[1] == MYSQL_CODE_DUPLICATE_KEY) {
                    //The INSERT query failed due to a key constraint violation.
                    //aka our public team code was not unique
                    $publicTeamCodeUnique = false;
                } else {
                    //its a different type of PDO exception which we still want to throw
                    throw $e;
                }
            }
        }

        if(!$publicTeamCodeUnique){
            //we exhausted our attempts and public team code still wasn't unique
            //something is likely wrong

            //this probably isn't the right exception type
            Throw new \RuntimeException("failed to create unique team code after \"$numberOfAttempts\" attempts");
        }

        $teamId = $this->connection->lastInsertId();
        $team = $this->getTeam($teamId);
        
        return $team;
        
    }

    public function createTeamTemp($team){
        $defaultScore = 0;
        $defaultCurrentTreasureIndex = 0;

        $teamRow = (array)$team;
        $this->checkAllowedParams($teamRow, ['name', 'max_players', 'active_treasure_hunt_id']);
        $this->checkRequiredParams($teamRow, ['name', 'max_players', 'active_treasure_hunt_id'] );

        $teamRow['current_treasure_index'] = 0;
        //is set later
        $teamRow['public_team_code'] = null;

        $teamRow['score'] = 0;
        //will execute our insert until we don't break the unique constraint
        //or we run out of attempts
        $publicTeamCodeUnique = false;
        $numberOfAttempts = 15;
        while((!$publicTeamCodeUnique && $numberOfAttempts > 0)) {
            $teamRow['public_team_code'] = $this->getPublicTeamCode();
            try {
                //assume its unique unless it fails
                $publicTeamCodeUnique = true;
                $numberOfAttempts--;
                $this->insertIntoTable('team', $teamRow);
            } catch (\PDOException $e) {
                if ($e->errorInfo[1] == MYSQL_CODE_DUPLICATE_KEY) {
                    //The INSERT query failed due to a key constraint violation.
                    //aka our public team code was not unique
                    $publicTeamCodeUnique = false;
                } else {
                    //its a different type of PDO exception which we still want to throw
                    throw $e;
                }
            }
        }

        if(!$publicTeamCodeUnique){
            //we exhausted our attempts and public team code still wasn't unique
            //something is likely wrong

            //this probably isn't the right exception type
            Throw new \RuntimeException("failed to create unique team code after \"$numberOfAttempts\" attempts");
        }

        $teamId = $this->connection->lastInsertId();
        $team = $this->getTeam($teamId);

        return $team;

    }
    
    public function getTeam($teamId){
//        $params = array(':team_id' => $teamId);
//        $sql = "SELECT * FROM team
//               WHERE id = :team_id";
//
//        $stmt = $this->db->executeSQL($this->connection, $sql, $params);
//
//        $stmt->setFetchMode(\PDO::FETCH_CLASS, Team::class);
//
//        $team = $stmt->fetch();

        $team = $this->readFromTableById($teamId, "team", Team::class);
        if(!$team){
            //team with given id not found
            return null;
        }


        return $team;
    }

    public function getTeamByPublicTeamCode($publicTeamCode){
        $params = array(':public_team_code' => $publicTeamCode);
        //team code match is case sensitive
        //use MySQL keyword 'BINARY' to ensure it matches
        //column public_team_code case sensitively
        $sql = "SELECT * FROM team
                WHERE public_team_code = BINARY :public_team_code";
        return $this->getObjectFromDb($sql, $params, Team::class);
    }

    public function getTeamByActiveTreasureHuntIdAndId($teamId, $activeTreasureHuntId){
        $params = array(':team_id' => $teamId, ':active_treasure_hunt_id' => $activeTreasureHuntId);
        $sql = "SELECT * FROM team
                WHERE active_treasure_hunt_id = :active_treasure_hunt_id AND id = :team_id";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, Team::class);

        $team = $stmt->fetch();
        if(!$team){
            //team not found
            return null;
        }
        return $team;
    }

    public function getAllTeamsInActiveTreasureHunt($activeTreasureHuntId){
        $params = array(':active_treasure_hunt_id' => $activeTreasureHuntId);
        $sql = "SELECT * FROM team
                    WHERE active_treasure_hunt_id = :active_treasure_hunt_id";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);


        $teams = $stmt->fetchAll(\PDO::FETCH_CLASS, Team::class );



        return $teams;
    }

    function getPublicTeamCode()
    {
        //(24 + 24 + 10)^4 = 11,316,496
        //so at 100,000 currently active teams it becomes a 0.9% chance of collision?
        //we gunno have like 10 teams so

        //returns an alphanumeric string of length specified by $LENGTH
        $LENGTH = 4;
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet); // edited
        //loop through our desired length
        for ($i=0; $i < $LENGTH; $i++) {
            //random_int is an implementation of ssl randomPseudoBytes
            //select a random character from our alphabet
            $token .= $codeAlphabet[random_int(0, $max-1)];
        }

        return $token;
    }

    function collectTreasure($teamId, $scoreIncrement, $currentTreasureIndex){

        $nextTreasureIndex = $currentTreasureIndex + 1;
        $sql = "Update team SET score = score + :score, current_treasure_index = :current_treasure_index
                WHERE id = :id";

        $params = [':score' => $scoreIncrement, ':current_treasure_index' => $nextTreasureIndex, ":id" => $teamId];

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        return $this->getTeam($teamId);
    }

    function updateScoreOnFeedbackSubmit($teamId, $scoreIncrement){


        $sql = "Update team SET score = score + :score
                WHERE id = :id";

        $params = [':score' => $scoreIncrement, ":id" => $teamId];

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        return $this->getTeam($teamId);
    }

}
