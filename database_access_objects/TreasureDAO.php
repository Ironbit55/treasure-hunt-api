<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 30/03/17
 * Time: 01:05
 */

namespace DatabaseAccessObjects;

use DbObjects\Treasure;


require_once 'database_access_objects/DatabaseAccessObject.php';
require_once 'db_objects/Treasure.php';

class TreasureDAO extends DatabaseAccessObject
{
    const TABLE_NAME = 'treasure';

    public function getTreasure($treasureId){
        return $this->readFromTableById($treasureId, "treasure",  Treasure::class);
    }

    public function createTreasure($treasure){


        $allowedColumns = ['clue', 'latitude', 'longitude', 'difficulty', 'default_order', 'treasure_hunt_template_id'];
        $requiredColumns = ['clue', 'latitude', 'longitude', 'difficulty', 'default_order', 'treasure_hunt_template_id'];

        $treasureRow = (array)$treasure;

        $this->checkRequiredParams($treasureRow, $requiredColumns);
        $this->checkAllowedParams($treasureRow, $allowedColumns);

        $treasureId = null;

        //will execute our insert until we don't break the unique constraint
        //or we run out of attempts
        $isQrCodeUnique = false;
        $maxAttempts = 5;
        $numberOfAttempts = $maxAttempts;
        while((!$isQrCodeUnique && $numberOfAttempts > 0)) {
            $treasureRow['qr_code'] = $this->generateQrCode();
            try {
                //assume its unique unless it fails
                $isQrCodeUnique = true;
                $numberOfAttempts--;
                $treasureId = $this->insertIntoTable(self::TABLE_NAME , $treasureRow);
            } catch (\PDOException $e) {
                if ($e->errorInfo[1] == MYSQL_CODE_DUPLICATE_KEY && $numberOfAttempts != 0) {
                    //The INSERT query failed due to a key constraint violation.
                    //aka our public team code was not unique
                    $isQrCodeUnique = false;
                } else {
                    if($numberOfAttempts == 0)
                    //its a different type of PDO exception which we still want to throw
                        //or we ran out of attempts so just throw the exception
                    throw $e;
                }
            }
        }

        if(!$treasureId){
            //we exhausted our attempts and public team code still wasn't unique
            //something is likely wrong

            //this probably isn't the right exception type
//            echo $treasureId;
//            echo $isQrCodeUnique;
            Throw new \RuntimeException("failed to create unique qr_code after \"$maxAttempts\" attempts");
        }


        return $this->readFromTableById($treasureId, 'treasure', Treasure::class);
    }

    public function getTreasures($treasureHuntTemplateId){
        $params = array(':treasure_hunt_template_id' => $treasureHuntTemplateId);
        $sql = "SELECT * FROM ". self::TABLE_NAME .
                " WHERE treasure_hunt_template_id = :treasure_hunt_template_id";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, Treasure::class);

        $activeTreasureHunts = $stmt->fetchAll();



        return $activeTreasureHunts;
    }

    public function generateQrCode(){
        //returns an alphanumeric string of length specified by $LENGTH
        $LENGTH = 24;
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

    public function getTeamTreasureTreasures($teamId){
        $params = array(':team_id' => $teamId);
        $sql = "Select * FROM treasure
                WHERE id in (Select treasure_id FROM `team-treasure` WHERE team_id = :team_id)";
        $stmt = $this->db->executeSQL($this->connection, $sql, $params);



        $treasures = $stmt->fetchAll(\PDO::FETCH_CLASS, Treasure::class );

        return $treasures;
    }


}