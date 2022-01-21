<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 14:51
 */

namespace ResourceObjectMappers;

/**
 * Class ObjectToResourcePropertiesEncoder
 * @package ResourceObjectMappers
 *
 * converts between an object and an array of their corresponding resource properties
 *
 */

class ObjectToResourcePropertiesEncoder{

    private $resourcePropertyNames;
    private $dbObjectFieldNames;
    function __construct($resourcePropertyNames, $dbObjectFieldNames)
    {
        if(count($resourcePropertyNames) != count($dbObjectFieldNames)){
            throw new \InvalidArgumentException("arrays were not of equal size");
        }

        //defines the mapping
        //maps a field name in the dbObjectFieldNames to the resource name in resourcePropertyName with the same index
        //and vice versa
        //so fiel
        $this->resourcePropertyNames = $resourcePropertyNames;
        $this->dbObjectFieldNames = $dbObjectFieldNames;
    }

    //converts the resource properties array to an object with corresponding field names
    public function resource__properties_decode($resourceProperties){
        $resourcePropertyData = $resourceProperties;
        $databaseObjectFields = array();


        //swaps the name of each element of resourcePropertyData to
        //the name of the corresponding database object field
        //
        // a resourcePropertyName maps to the dbObjectFieldName of the same index

        foreach($this->resourcePropertyNames as $index => $resourcePropertyName){
            if(array_key_exists($resourcePropertyName, $resourcePropertyData)) {
                //set the database object fields to
                $databaseObjectFields[$this->dbObjectFieldNames[$index]] = $resourcePropertyData[$resourcePropertyName];
            }

        }

        return (object)$databaseObjectFields;
    }

    //converts the fields of the object to the corresponding key-value array
    //of resource property names -> value
    public function resource_properties_encode($databaseObject){
        if(!$databaseObject){
            return null;
        }
        $databaseObjectFields = (array)$databaseObject;
        $resourcePropertyData = array();


        //swaps the name of each element of $databaseObjectFields to
        //the name of the corresponding database object field
        //
        // a resourcePropertyName maps to the dbObjectFieldName of the same index

        foreach($this->dbObjectFieldNames as $index => $dbObjectFieldName){
            if(array_key_exists($dbObjectFieldName, $databaseObjectFields)) {
                $resourcePropertyData[$this->resourcePropertyNames[$index]] = $databaseObjectFields[$dbObjectFieldName];
            }

        }

        return $resourcePropertyData;


    }


}