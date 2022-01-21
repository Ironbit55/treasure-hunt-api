<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 26/03/2017
 * Time: 22:47
 */

namespace DatabaseAccessObjects;

use DbObjects\ActiveTreasureHunt;

require_once 'database_access_objects/DatabaseAccessObject.php';
require_once 'db_objects/ActiveTreasureHunt.php';



//Database Access object for interacting with the active_treasure_hunt table
class ActiveTreasureHuntDAO extends DatabaseAccessObject
{
    public function getActiveTreasureHunt($id){
        $params = [':id' => $id];
        $sql = "SELECT * FROM ". "active_treasure_hunt" .  " WHERE id = :id";
        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, ActiveTreasureHunt::class);

        $activeTreasureHunt = $stmt->fetch();

        if(!$activeTreasureHunt){
            //could not find organiser with given username
            return null;
        }

        return $activeTreasureHunt;
    }
    public function getAllActiveTreasureHunts(){
        $sql = "SELECT * FROM ". "active_treasure_hunt";
        $stmt = $this->db->executeSQL($this->connection, $sql, []);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, ActiveTreasureHunt::class);

        $activeTreasureHunts = $stmt->fetchAll();



        return $activeTreasureHunts;
    }

    public function getAllActiveTreasureHuntsBelongingToOrganiser($organiserId){
        $params = array(':organiser_id' => $organiserId);
        $sql = "SELECT * FROM active_treasure_hunt
                WHERE organiser_id = :organiser_id";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, ActiveTreasureHunt::class);

        $activeTreasureHunts = $stmt->fetchAll();



        return $activeTreasureHunts;
    }

    public function createActiveTreasureHunt($organiserId){
        $startTime = time();
        $sql = "INSERT INTO active_treasure_hunt (start_time, organiser_id) VALUES (:start_time, :organiser_id)";

        $params = [':start_time' => $startTime, ':organiser_id' => $organiserId];

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $activeTreasureHuntId = $this->connection->lastInsertId();
        $activeTreasureHunt = $this->getActiveTreasureHunt($activeTreasureHuntId);

        return $activeTreasureHunt;
    }

    public function startActiveTreasureHunt($activeTreasureHuntId){
        $phpStartTime = time();
        $mySqlStartTime = date( 'Y-m-d H:i:s', $phpStartTime );
        //$phpdate = strtotime( $mysqldate );

        $sql = "Update active_treasure_hunt SET start_time = :start_time, is_started = :is_started WHERE id = :id";
        $params = [':start_time' => $mySqlStartTime, ':is_started' => true, ":id" => $activeTreasureHuntId];

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);


        $activeTreasureHunt = $this->getActiveTreasureHunt($activeTreasureHuntId);

        return $activeTreasureHunt;
    }

    public function stopActiveTreasureHunt($activeTreasureHuntId){
        $sql = "Update active_treasure_hunt SET is_finished = :is_finished, finish_time = :finish_time WHERE id = :id";

        $phpFinishTime = time();
        $mySqlFinishTime = date( 'Y-m-d H:i:s', $phpFinishTime );


        $params = [':is_finished' => true, ':finish_time' => $mySqlFinishTime, ":id" => $activeTreasureHuntId];

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);


        $activeTreasureHunt = $this->getActiveTreasureHunt($activeTreasureHuntId);

        return $activeTreasureHunt;
    }

    public function createActiveTreasureHuntTemp($activeTreasureHunt){
        $activeTreasureHuntRow = (array)$activeTreasureHunt;


        $allowedColumns = ['organiser_id', 'name', 'treasure_hunt_template_id'];
        $requiredColumns = ['organiser_id', 'name', 'treasure_hunt_template_id'];



        checkAllowedProperties($activeTreasureHuntRow, $allowedColumns );
        checkRequiredProperties($activeTreasureHuntRow, $requiredColumns);



        $activeTreasureHuntId = $this->insertIntoTable('active_treasure_hunt', (array)$activeTreasureHunt);

        return $this->getActiveTreasureHunt($activeTreasureHuntId);
    }



}