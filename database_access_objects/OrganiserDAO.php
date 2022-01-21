<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 09/03/2017
 * Time: 15:30
 */

namespace DatabaseAccessObjects;

use DbObjects\Organiser;

require_once 'db_objects/Organiser.php';
require_once 'include/PDO_db.php';
require_once 'database_access_objects/DatabaseAccessObject.php';

/**
 * Manages interacting with organisers in the database e.g CRUD
 */
class OrganiserDAO extends DatabaseAccessObject
{

    const TABLE_NAME = 'organiser';

    function createOrganiser($organiser){


        // $username = $mysqli->real_escape_string($username);
        // $password = $mysqli->real_escape_string($password);
//        $params = [':username' => $organiser->username, ':password' => $organiser->password, ':first_name' => $organiser->first_name, ':last_name' => $organiser->last_name ];
//        $sql = "INSERT INTO ". self::TABLE_NAME .  "(username, password, first_name, last_name) Values (:username, :password, :first_name, :last_name)";
//
//        $stmt = $this->db->executeSQL($this->connection, $sql, $params);
        $organiserRow = (array)$organiser;

        $this->checkAllowedParams($organiserRow, ['username', 'password', 'first_name', 'last_name']);
        $this->checkRequiredParams($organiserRow, ['username', 'password']);
        $organiserId = $this->insertIntoTable("organiser", $organiserRow);
        return $this->readFromTableById($organiserId, 'organiser', Organiser::class);



    }

    function getAllOrganisers(){
        $sql = "SELECT * FROM ". self::TABLE_NAME;

        $stmt = $this->db->executeSQL($this->connection, $sql, []);
        $organisers = $stmt->fetchAll(\PDO::FETCH_CLASS, Organiser::class );

        return $organisers;
    }

    function getOrganiserByUsername($username){
        $params = [':username' => $username];
        $sql = "SELECT * FROM ". self::TABLE_NAME .  " WHERE username = :username";
        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, Organiser::class);

        $organiser = $stmt->fetch();

        if(!$organiser){
            //could not find organiser with given username
            return null;
        }

        return $organiser;

    }

    function getOrganiserById($id){
        return $this->readFromTableById($id, 'organiser', Organiser::class);
    }

    function getOrganiserByToken($token){

        $params = [':token' => $token];

        $sql = "SELECT * FROM ". self::TABLE_NAME .  " WHERE token = :token";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, Organiser::class);

        $organiser = $stmt->fetch();

        if(!$organiser){
            return null;
        }
        return $organiser;


    }

    function getToken($password, $hashedPassword){
        if(password_verify($password, $hashedPassword)) {


            $token = bin2hex(random_bytes(8)); //generate a random token
            return  $token;
        }
        return null;
    }

    function updateToken($username, $token){

        //the expiration date will be in one hour from the current moment
        $tokenExpiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $params = [':token' => $token, ':token_expiration' => $tokenExpiration, ':username' => $username];

        $sql = "UPDATE ". self::TABLE_NAME .  " SET token = :token, token_expire = :token_expiration WHERE username = :username";

        $stmt = $this->db->executeSQL($this->connection, $sql, $params);
        return true;
    }

    function validateOrganiser($username, $password){
        $organiser = $this->getOrganiserByUsername($username);
        if(!$organiser){
            //organiser with that username could not be found
            return null;
        }
        if(password_verify($password, $organiser->password)) {


            $token = bin2hex(random_bytes(8)); //generate a random token


            $this->updateToken($username, $token);
            return  $token;
        }
        return null;
        //password was incorrect
    }


}