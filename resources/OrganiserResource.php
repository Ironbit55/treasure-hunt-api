<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 15:07
 */

namespace Resources;

require_once 'resources/Resource.php';

class OrganiserResource extends Resource
{

    private $organiserId;
    // TODO: Implement getRepresentation() method.
    function __construct(array $properties)
    {
        //$this->organiserId = $organiserId;
        $propertyNames = ['id', 'first_name', 'last_name', 'username', 'password', 'token', 'active_treasure_hunts', 'treasure_hunt_templates'];
        $requiredProperties = ['username', 'password'];


//        $this->addPropertyValueValidation('password', function($propertyValue){
//            //username must be between 6 and 18 characters
//            //accepts, alphanumeric characters + "-" and "_"
//
//            return preg_match("/^[a-z0-9_-]{6,18}$/", $propertyValue);
//        });

//        $this->addPropertyValueValidation('username', function($propertyValue){
//            //username must be between 3 and 16 characters
//            //accepts, alphanumeric characters + "-" and "_"
//            return preg_match("/^[a-z0-9_-]{3,16}$/", $propertyValue);
//        });

        parent::__construct($propertyNames, $requiredProperties, $properties);
    }
}