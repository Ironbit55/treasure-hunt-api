<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 01/05/2017
 * Time: 10:39
 */

namespace DatabaseAccessObjects;

use DbObjects\Feedback;

require_once 'database_access_objects/DatabaseAccessObject.php';
require_once 'db_objects/Feedback.php';

class FeedbackDAO extends DatabaseAccessObject
{

    public function createFeedback($feedback){
        checkRequiredProperties((array)$feedback, ["active_treasure_hunt_id", "player_token", "name", "question_2", "question_3", "question_4", "question_5", "question_6", "question_7", "question_8",
            "question_9", "question_10", "question_11", "question_12", "question_13", "question_14",]);
        checkRequiredProperties((array)$feedback, ["active_treasure_hunt_id", "player_token", "name", "question_2", "question_3", "question_4", "question_5", "question_6", "question_7", "question_8",
            "question_9", "question_10", "question_11", "question_12", "question_13", "question_14",]);

        $phpSubmitTime = time();
        $mySqlSubmitTime = date( 'Y-m-d H:i:s', $phpSubmitTime );

        $feedback['submit_time'] = $mySqlSubmitTime;

        $feedbackId = $this->insertIntoTable('feedback', $feedback);

        return $this->readFromTableById($feedbackId, 'feedback', Feedback::class );


    }

    public function getAllFeedback(){
        $params = array();
        $sql = "SELECT * FROM feedback";

        $stmt = $this->db->executeSQL($this->connection, $sql, []);


        $allFeedback = $stmt->fetchAll(\PDO::FETCH_CLASS, Feedback::class );



        return $allFeedback;
    }
}