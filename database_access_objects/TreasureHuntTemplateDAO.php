<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 18/04/17
 * Time: 19:24
 */

namespace DatabaseAccessObjects;


use DbObjects\TreasureHuntTemplate;


require_once 'db_objects/TreasureHuntTemplate.php';
require_once 'include/PDO_db.php';
require_once 'database_access_objects/DatabaseAccessObject.php';

class TreasureHuntTemplateDAO extends DatabaseAccessObject
{
    const TABLE_NAME = 'treasure_hunt_template';
    function getTreasureTemplate($treasureHuntTemplateId){
        return $this->readFromTableById($treasureHuntTemplateId, self::TABLE_NAME, TreasureHuntTemplate::class);
    }

    public function getAllTreasureHuntTemplatesBelongingToOrganiser($organiserId){
        $params = array(':organiser_id' => $organiserId);
        $sql = "SELECT * FROM ". self::TABLE_NAME .
                " WHERE organiser_id = :organiser_id";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, TreasureHuntTemplate::class);

        $treasureHuntTemplates = $stmt->fetchAll();



        return $treasureHuntTemplates;
    }

    function createTreasureHuntTemplate($treasureHuntTemplate){
        $allowedColumns = ['name', 'organiser_id'];
        $requiredColumns = ['name', 'organiser_id'];

        $createTreasureHuntTemplateId = $this->safeInsertIntoTable(self::TABLE_NAME,
            (array)$treasureHuntTemplate, $allowedColumns, $requiredColumns );

        return $this->getTreasureTemplate($createTreasureHuntTemplateId);
    }
}