<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 26/03/2017
 * Time: 22:46
 */

namespace DbObjects;


//represents a record from the active_treasure_hunt
//as an object
class ActiveTreasureHunt
{
    public $id;
    public $is_started;
    public $is_finished;
    public $start_time;
    public $finish_time;
    public $organiser_id;
    public $treasure_hunt_template_id;
}