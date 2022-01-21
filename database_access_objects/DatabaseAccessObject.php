<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DatabaseAccessObjects;

require_once 'include/PDO_db.php';

//handles interacting with the database
//this base object sets up the db connection
//and has some useful helper functions
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;
use Slim\Exception\SlimException;
/**
 * Description of DbObject
 *
 * @author Edward Curran
 */
class DatabaseAccessObject {
    protected $db;
    protected $connection;
    
    //private $teamId;
    function __construct()
    {
        $this->db = new \PDO_db();
        $this->connection = $this->db->connect();


    }
    protected function selectFromTableBy($selectionParams, $tableName, $objectType){

        //ToDO: implement this
        throw new \BadFunctionCallException("function not implemented");
    }
    protected function readFromTableById($id, $tableName, $objectType ){
        $params = array(':id' => $id);
        $sql = "SELECT * FROM $tableName
                WHERE id = :id";


        return $this->getObjectFromDb($sql, $params, $objectType);

    }

    protected function getObjectFromDb($sql, $params, $objectType){
        $stmt = $this->db->executeSQL($this->connection, $sql, $params);

        $stmt->setFetchMode(\PDO::FETCH_CLASS, $objectType);

        $object = $stmt->fetch();

        return $object;
    }



    protected function insertIntoTable($tableName, $columnNameValuePairs){
        $sql = 'INSERT INTO ' . $tableName . ' ';
        $columns = '(';
        $values = '(';
        $params = array();
        foreach ($columnNameValuePairs as $key => $value) {
            //create string of column names, from the keys, separated by commas and each enclosed by an uptick
            $columns .= '`' . $key . '`, ';
            //insert ? as placeholder for each value we want to insert when we execute the statement
            $values .= "?, ";
            //make a params array made up of each value in the correct order
            //which we will use to execute the statement with the correct values
            $params[] = $value;
        }
        //remove trailing commas and close brackets
        $columns = rtrim($columns, ', ') . ')';
        $values = rtrim($values, ', ') . ')';
        //form final sql statement
        $sql .= $columns . ' VALUES ' . $values;

        $q = $this->connection->prepare($sql);
        //execute with our params
        $q->execute($params);

        //return the id of the row we just inserted
        return $this->connection->lastInsertId();
    }

    function safeInsertIntoTable($tableName, $columnValuePairs, $allowedColumns, $requiredColumns){
        $this->checkAllowedParams($columnValuePairs, $allowedColumns);
        $this->checkRequiredParams($columnValuePairs, $requiredColumns);
        return $this->insertIntoTable($tableName, $columnValuePairs);
    }
    protected function selectFromTable($tableName, $withAttributes){

    }

    protected function checkRequiredParams(array $associativeArray, array $requiredParams){
        foreach($requiredParams as $requiredParam){
            if(!array_key_exists($requiredParam, $associativeArray)){
                throw new \InvalidArgumentException("DAO: row does not have the minimum required field \"$requiredParam\"");
            }
        }
    }

    protected function checkAllowedParams(array $associativeArray, array $allowedParams){
        foreach($associativeArray as $coloumnName => $columnValue){
            if(!in_array($coloumnName, $allowedParams)){
                throw new \InvalidArgumentException("DAO: row has not allowed field: \"$coloumnName\"");
            }
        }
    }

    protected function getArraySubset(array $associativeArray, array $keys){
        if(!$associativeArray){
            //we know array is null so we can just return null early
            return null;
        }

        $arraySubset = array();
        //foreach key specified
        foreach ($keys as $key) {
            //make sure the given array does contain this key
            if (array_key_exists($key, $associativeArray)) {
                $value = $associativeArray[$key];
                //add element to our subarray
                $arraySubset[$key] = $value;
                continue;

            } else {
                //array did not contain the specified key / element
                throw new \InvalidArgumentException("field name \"$key\" did not exist in object");
            }
        }

        return $arraySubset;
    }
}
