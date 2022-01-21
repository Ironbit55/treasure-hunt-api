<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 17:19
 */

namespace Resources;

require_once 'resources/Resource.php';

class TeamResource extends Resource{
    function __construct($propertyData)
    {
        $propertyNames = ['id', 'current_treasure_index', 'name', 'score', 'public_team_code', 'max_players', 'active_treasure_hunt_id', 'players', 'collectable_treasures'];
        $requiredProperties = ['active_treasure_hunt_id', 'name', 'max_players'];
        parent::__construct($propertyNames, $requiredProperties, $propertyData);
    }
}