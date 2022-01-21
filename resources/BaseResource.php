<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 16:50
 */

namespace Resources;

require_once 'resources/Resource.php';

class BaseResource extends Resource{

    function __construct($propertyData)
    {
        $propertyNames = ["organisers"];
        $requiredProperties = [];
        parent::__construct($propertyNames, $requiredProperties, $propertyData);
    }
}