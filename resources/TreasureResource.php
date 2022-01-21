<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 18/04/17
 * Time: 19:31
 */

namespace Resources;

require_once 'resources/Resource.php';

class TreasureResource extends Resource
{
    function __construct(array $propertyData)
    {
        $propertyNames = ['id', 'clue', 'latitude', 'longitude', 'difficulty', 'default_order', 'qr_code', 'treasure_hunt_template_id'];
        $requiredProperties = ['clue', 'latitude', 'longitude', 'difficulty', 'default_order', 'treasure_hunt_template_id'];
        parent::__construct($propertyNames, $requiredProperties, $propertyData);
    }
}