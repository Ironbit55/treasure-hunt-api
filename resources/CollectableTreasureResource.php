<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 20/04/17
 * Time: 12:45
 */

namespace Resources;

require_once 'resources/Resource.php';
class CollectableTreasureResource extends Resource
{
    function __construct($propertyData)
    {
        $propertyNames = ['clue', 'team_id', 'latitude', 'longitude', 'has_been_found', 'order', 'score', 'found_time', 'qr_code'];
        $requiredProperties = ['order', 'score'];

        parent::__construct($propertyNames, $requiredProperties, $propertyData);
    }
}