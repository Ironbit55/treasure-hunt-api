<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 07/03/2017
 * Time: 20:50
 */

namespace DatabaseAccessObjects;

use Slim\Exception\NotFoundException;
use Slim\Exception\SlimException;
use DbObjects\Player;

require_once 'db_objects/Player.php';
require_once 'include/PDO_db.php';

/**
 * Manages interacting with Players in the database e.g CRUD
 */
class PlayerDAO extends DatabaseAccessObject
{

    public function createPlayer($name, $teamId){
        try {
            $sql = "INSERT INTO player (name, team_id, token) VALUES (:name, :team_id, :token )";
            $token = bin2hex(random_bytes(8));
            $params = [':name' => $name, ':team_id' => $teamId, ':token' => $token];

            $stmt = $this->db->executeSQL($this->connection, $sql, $params);
        }catch(\PDOException $e) {
            //TODO throw exception which is handled by slim and returns a fail status code, and message
            throw $e;
        }

        //player insert successfull
        $playerId = $this->connection->lastInsertId();

        //get player we just inserted
        $player = $this->getPlayerById($playerId);

        return $player;
    }

    public function createPlayerFromObject($player){
        $playerRow = (array)$player;

        $this->checkAllowedParams($playerRow, ['name','team_id']);
        $this->checkRequiredParams($playerRow, ['name','team_id'] );


        //generate token
        $playerRow['token'] = bin2hex(random_bytes(8));


        $playerId = $this->insertIntoTable('player', $playerRow);
        //player insert successfull

        //get player we just inserted
        $player = $this->getPlayerById($playerId);

        return $player;
    }

    public function getPlayerByTeamIdAndId($playerId, $teamId){
        try {
            $params = array(':team_id' => $teamId, ':player_id' => $playerId);
            $sql = "SELECT * FROM player
                WHERE team_id = :team_id AND id = :player_id";

            $stmt = $this->db->executeSQL($this->connection, $sql, $params);

            $stmt->setFetchMode(\PDO::FETCH_CLASS, Player::class);

            $player = $stmt->fetch();
            if(!$player){
                //TODO throw exception which is handled by slim and returns a fail status code, and message
                return null;
            }
            return $player;


        } catch(\PDOException $e) {
            //TODO throw exception which is handled by slim and returns a fail status code, and message
            echo 'ERROR: ' . $e->getMessage();
        }
    }

    public function getPlayerById($playerId){
        try {
            $params = array(':player_id' => $playerId);
            $sql = "SELECT * FROM player
                WHERE id = :player_id";

            $stmt = $this->db->executeSQL($this->connection, $sql, $params);

            $stmt->setFetchMode(\PDO::FETCH_CLASS, Player::class);

            $player = $stmt->fetch();

            if(!$player){
                //player not found
               return null;
            }

            //player successfully found so return it
            return $player;


        } catch(\PDOException $e) {

            throw $e;
            //throw exception which is handled by slim and returns a fail status code

        }
    }

    public function getPlayerByToken($token){
        $sql = "SELECT * FROM player
                WHERE token = :token";

        $params = [':token' => $token];

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, Player::class);

        if(!$player = $stmt->fetch()){
            //sql query did not find a player matching the token
            //TODO throw exception which is handled by slim and returns a fail status code, and custom message
            return null;
        }

        //sql query successfully found player
        return $player;



    }

    public function getAllPlayersInTeam($teamId){
        try {
            $params = array(':team_id' => $teamId);
            $sql = "SELECT * FROM player
                    WHERE team_id = :team_id";

            $stmt = $this->db->executeSQL($this->connection, $sql, $params);


            $players = $stmt->fetchAll(\PDO::FETCH_CLASS, Player::class );

            //$stmt->setFetchMode(\PDO::FETCH_CLASS, 'PlayerTestTwo');
            //while($player = $stmt->fetch()) {
            //  $players[] = $player;
            //  //echo $player;
            //}

            return $players;
        } catch(\PDOException $e) {
            echo 'ERROR: ' . $e->getMessage();
        }
    }

}