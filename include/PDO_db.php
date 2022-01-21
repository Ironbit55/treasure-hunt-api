<?php

/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 05/03/2017
 * Time: 18:20
 */
//Including the constants.php file to get the database constants
require_once('constants.php');

/*
 * Handles connecting to and executing queries on the database
 */
class PDO_db
{

    static $connection;

//Class constructor
    function __construct()
    {

    }

//This method will connect to the database
    function connect()
    {
        //NOTE: we just throw the exceptions and slims 'errorHandler' will handle it and return a server error
        // Try and connect to the database
        if(!isset(self::$connection)) {

            try {
                self::$connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USERNAME, DB_PASSWORD);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                //Checking if any error occured while connecting
                throw $e;
            }



        }
        //finally returning the connection link
        return self::$connection;
    }


    function executeSQL(PDO $connection, $sql, $params){

        try {
            $stmt = $connection->prepare($sql);

            $stmt->execute($params);

            return $stmt;
        }catch (PDOException $e) {
            //Checking if any error occured while connecting
            throw $e;
        }
    }
}
