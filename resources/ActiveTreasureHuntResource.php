<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 15:12
 */

namespace Resources;

require_once 'resources/Resource.php';

class ActiveTreasureHuntResource extends Resource {
    function __construct(array $propertyData)
    {
        $propertyNames = ['id', 'name', 'is_started', 'start_time',  'is_finished', 'finish_time', 'organiser_id', 'treasure_hunt_template_id', 'teams'];
        $requiredProperties = ['organiser_id', 'name', 'treasure_hunt_template_id'];
        parent::__construct($propertyNames, $requiredProperties, $propertyData);
    }
}