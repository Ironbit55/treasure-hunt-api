<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 14:48
 */

namespace Resources;

require_once 'resources/Resource.php';

abstract class Resource{
    protected $propertyNames;
    protected $requiredProperties;
    protected $properties;
    protected $validPropertyFunctions = [];
    // TODO: Implement getRepresentation() method.
    function __construct(array $propertyNames, array $requiredProperties, $propertyData )
    {
        $this->propertyNames = $propertyNames;
        $this->requiredProperties = $requiredProperties;
        $this->properties = [];
        //permissions
        $this->setProperties($propertyData);
    }

    function setProperties(array $propertyData)
    {
        foreach ($propertyData as $potentialPropertyName => $potentialPropertyValue){
            $this->setProperty($potentialPropertyName, $potentialPropertyValue);
        }
        foreach($this->requiredProperties as $requiredPropertyName){
            if(!array_key_exists($requiredPropertyName, $this->properties)){
                //this property is required but did not exist in propertyData
                throw new \UnprocessableResourceException("resource body did not contain \"$requiredPropertyName\" property");

            }
        }
        //property data was parsed successfully

    }

    public function setProperty($propertyName, $propertyValue){
        if($this->isValidProperty($propertyName)){
            //property name is a valid property so we can set the resources property value

            //we check the propertys value is valid
            $result = $this->validatePropertyValue($propertyName, $propertyValue);

            if($this->validatePropertyValue($propertyName, $propertyValue)) {
                //property name is a valid property so we can set the resources property value
                $this->properties[$propertyName] = $propertyValue;
            }else{

                throw new \UnprocessableResourceException("value for property: \"$propertyName\" is not valid");
            }
        }else{

            //trying to set a property that doesn't exist
            throw new \UnprocessableResourceException("property \"$propertyName\" is not a valid property");

        }
    }

    protected function addPropertyValueValidation($propertyName, $validationFunction){

        $this->validPropertyFunctions[$propertyName] = $validationFunction;

    }

    protected function validatePropertyValue($propertyName, $propertyValue){
        if(array_key_exists($propertyName, $this->validPropertyFunctions)) {
            return call_user_func_array($this->validPropertyFunctions[$propertyName], array($propertyValue));
        }
        return true;
    }

    public function propertyIsSet($propertyName){
        return array_key_exists($propertyName, $this->properties);
    }

    public function isValidProperty($propertyName){
        return in_array($propertyName, $this->propertyNames);
    }

    public function getProperty($propertyName){
        if(!$this->isValidProperty($propertyName)){
            throw new \InvalidArgumentException("property \"$propertyName\" is not a valid property");

        }
        if($this->propertyIsSet($propertyName)){
            return $this->properties[$propertyName];
        }else{
            return null;
        }
    }
    public function getPropertiesSubset(array $subset){
        $associativeArray = $this->properties;
        $keys = $subset;

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
                if(!$this->isValidProperty($key)){
                    throw new \InvalidArgumentException("tried to get an invalid property \"$key\" from resource");
                }
                //the key points to a property which is a valid property its just not set in this resource
            }
        }

        return $arraySubset;

    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return $this->properties;
    }
}