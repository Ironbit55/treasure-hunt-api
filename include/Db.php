<?php
//Variable to store database link
require_once('constants.php');

class Db
{

    static $connection;

//Class constructor
    function __construct()
    {

    }

//This method will connect to the database
    function connect()
    {

        // Try and connect to the database
        if(!isset(self::$connection)) {

            //connecting to mysql database
            self::$connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

            //Checking if any error occured while connecting
        }
        if (self::$connection === false) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        //finally returning the connection link
        return self::$connection;
    }
}


/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 06/12/2016
 * Time: 10:42
 */