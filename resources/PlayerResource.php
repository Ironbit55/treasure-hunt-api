<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 20/04/17
 * Time: 10:07
 */

namespace Resources;

require_once 'resources/Resource.php';

class PlayerResource extends Resource
{
    function __construct($propertyData)
    {
        $propertyNames = ['id', 'name','team_id', 'token'];
        $requiredProperties = ['name', 'team_id'];
        parent::__construct($propertyNames, $requiredProperties, $propertyData);
    }
}