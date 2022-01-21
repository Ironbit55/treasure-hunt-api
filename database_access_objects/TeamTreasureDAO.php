<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 20/04/17
 * Time: 13:49
 */

namespace DatabaseAccessObjects;

use DbObjects\TeamTreasure;
use DbObjects\Treasure;

require_once 'database_access_objects/DatabaseAccessObject.php';
require_once 'db_objects/TeamTreasure.php';

class TeamTreasureDAO extends DatabaseAccessObject
{
    public function getTeamTreasures($teamId){
        $params = array(':team_id' => $teamId);

        $sql = "SELECT * FROM `team-treasure`
                WHERE team_id = :team_id";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);



        $teamTreasures = $stmt->fetchAll(\PDO::FETCH_CLASS, TeamTreasure::class );



        return $teamTreasures;
    }

    function getTeamTreasure($teamId, $order){
        $params = array(':team_id' => $teamId, ":order" => $order);

        $sql = "SELECT * FROM `team-treasure`
                WHERE team_id = :team_id AND `order` = :order";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, TeamTreasure::class);

        $teamTreasure = $stmt->fetch();

        //sql query successfully team treasure
        return $teamTreasure;
    }

    public function createTeamTreasure($teamTreasure){
        $teamTreasureRow = (array)$teamTreasure;

        $this->checkAllowedParams($teamTreasureRow, ['team_id', 'treasure_id', 'order', 'score']);
        $this->checkRequiredParams($teamTreasureRow, ['team_id', 'treasure_id', 'order', 'score']);

        $teamTreasureId = $this->insertIntoTable('team-treasure', $teamTreasureRow);
    }

    public function createMultipleTeamTreasure(array $teamTreasures){

        $sql = "INSERT INTO `team-treasure` (team_id, treasure_id, `order`, score) VALUES (:team_id, :treasure_id, :order, :score)";
        $query = $this->connection->prepare($sql);

        foreach($teamTreasures as $teamTreasure){
            $teamTreasureRow = $this->getArraySubset((array)$teamTreasure, ['team_id', 'treasure_id', 'order', 'score']);

            $this->checkAllowedParams($teamTreasureRow, ['team_id', 'treasure_id', 'order', 'score']);
            $this->checkRequiredParams($teamTreasureRow, ['team_id', 'treasure_id', 'order', 'score']);

            $query->execute($teamTreasureRow);
        }

        //success

    }

    public function collectTreasure($teamId, $order){

        $hasBeenFound = true;
        $phpFoundTime = time();
        $mySqlFoundTime = date( 'Y-m-d H:i:s', $phpFoundTime );

        $sql = "Update `team-treasure` SET has_been_found = :has_been_found, found_time = :found_time
                WHERE team_id = :team_id AND `order` = :order";

        $params = [':has_been_found' => $hasBeenFound, ':found_time' => $mySqlFoundTime, ":team_id" => $teamId, ":order" => $order];

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);


//        echo json_encode($this->getTeamTreasure($teamId, $order));
//        die();
        //success

    }
}