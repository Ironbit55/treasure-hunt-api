<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 16:07
 */

namespace Authorisation;

/*
    * Permission
    * name: name of permission
    * attributes: the attributes a user with this permission has access to, depending on the
    * operation they're performing
    * function: a function used to check whether the authUser can gain
    * this permission
    *
    * A permission defines the operations a user can make to a resource
    *  and the properties they can make that operation on
    *  It also defines the condition the AuthUser must pass to obtain this permission
    */
class Permission{

    private $name;
    private $attributes = [];
    private $obtainPermissionFunction;

    function __construct($name){
        $this->name = $name;
    }

    private function withAttributes($operationType, array $attributes){
        $copy = clone $this;
        $copy->attributes[$operationType] = $attributes;
        return $copy;
    }
    //will register attributes to all operation types
    function withAnyAttributes(array $attributes){
        return $this->withAttributes("ANY", $attributes);
    }
    function withReadAttributes(array $attributes){
        return $this->withAttributes("READ", $attributes);
    }

    function withCreateAttributes(array $attributes){
        return $this->withAttributes("CREATE", $attributes);
    }
    function withObtainPermissionFunction($obtainPermissionFunction){
        $copy = clone $this;
        $copy->obtainPermissionFunction = $obtainPermissionFunction;
        return $copy;
    }

    private function getAttributes($operationType){
        //check attributes registered to this operation exists
        if(array_key_exists($operationType, $this->attributes)) {
            return $this->attributes[$operationType];
        }
        //they don't so try returning any attributes registered to ANY
        if(array_key_exists("ANY", $this->attributes)) {
            return $this->attributes["ANY"];
        }
        //no attributes exist so return empty array
        return [];
    }

    public function getReadAttributes(){
        return $this->getAttributes("READ");
    }

    public function getCreateAttributes(){
        return $this->getAttributes("CREATE");
    }

    function getName(){
        return $this->name;
    }
    function getObtainPermissionFunction(){
        return $this->obtainPermissionFunction;
    }



}