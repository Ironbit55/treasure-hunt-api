<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 18/04/17
 * Time: 19:12
 */

namespace Resources;

require_once 'resources/Resource.php';

class TreasureHuntTemplateResource extends Resource
{
    function __construct(array $propertyData)
    {
        $propertyNames = ['id', 'name', 'organiser_id', 'treasures'];
        $requiredProperties = ['name', 'organiser_id'];
        parent::__construct($propertyNames, $requiredProperties, $propertyData);
    }
}