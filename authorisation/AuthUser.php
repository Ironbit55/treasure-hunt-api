<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 19/03/2017
 * Time: 14:51
 */

namespace Authorisation;


abstract class AuthUserType{
    const Admin = 0;
    const Organiser = 1;
    const Player = 2;
    const BaseUser = 3;
}

/**
 * AuthUser stores the different ways a user can be authenticated
 * [Admin, Organiser, Player]
 * authUserType stores what type of user the authUser is authenticated as
 * the userObject stores the object the user is authenticated as
 * (admin is just null for the moment...)
*/

class AuthUser
{

    private $permissionsName;
    function __construct($authUserType, $userObject)
    {
        if($authUserType < AuthUserType::Admin || $authUserType > AuthUserType::BaseUser){
            throw new \InvalidArgumentException("invalid auth user type");
        }
        $this->authUserType = $authUserType;
        $this->userObject = $userObject;
    }
    function isAdmin(){
        return $this->authUserType == AuthUserType::Admin;
    }

    function isOrganiser(){
        return $this->authUserType == AuthUserType::Organiser;
    }

    function isPlayer(){
        return $this->authUserType == AuthUserType::Player;
    }
    function isBaseUser(){
        return $this->authUserType == AuthUserType::BaseUser;
    }

    function getUserObject(){
        return $this->userObject;
    }

    function hasPermission($name){
        return $this->permissionsName == $name;
    }

    function setPermission($name){
        $this->permissionsName = $name;
    }

    function getPermission(){
        return $this->permissionsName;
    }



}