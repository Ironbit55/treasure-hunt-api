<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 14:57
 */

namespace Authorisation;


class PermissionsPolicy{
    /**
     * @var Permission[]
     */
    private $permissions;



    function __construct(){


    }

    public function getAuthorisedPermission(AuthUser $authUser){
        $userPermissionName = $authUser->getPermission();

        return $this->getPermission($userPermissionName);
    }

    /*
    * adds a new permission to the permission policy
    */
    public function addPermission(Permission $permission){
        $this->permissions[$permission->getName()] = $permission;
    }

    /*
     * register permission
     * name: name of permission
     * attributes: the attributes a user with this permission can view
     */
//    public function registerPermission($name, array $attributes){
//        $this->permissions[$name] = new Permission($name, $attributes, null);
//    }

    private function getPermission($name){
        if(!$permission = $this->permissions[$name]){
            new \InvalidArgumentException("Permission with name \"$name\" could not be found in this policy");
        }
        return $permission;
    }

    public function gainPermission($params, AuthUser $authUser){
        foreach ($this->permissions as $permissionName => $permission) {
            //check if authUser matches criteria for this resource to gain new permission

            //can only check permissions with an obtain permission function
            if ($permission->getObtainPermissionFunction()) {
                if (call_user_func_array($permission->getObtainPermissionFunction(), $params) == true) {
                    //authUser is authorised for this resource(resource described by params)
                    $authUser->setPermission($permissionName);

                    return $permissionName;
                }
            }
        }

        //authuser did to gain a new permission
        return null;


    }
    public function authorise(AuthUser $authUser){
        //we check the permissions array to see if it contains
        //the permission the authUser has

        //this tell us whether the permission policy has
        //an implementation for the authUsers current permission

        if(array_key_exists($authUser->getPermission(), $this->permissions)){
            //auth user has a valid permission, so we get this permission policies
            //implementation of the permission
            return $this->getPermission($authUser->getPermission());
        }

        //auth user does not
        throw new \UnauthorisedResourceException("user is not authorised for this resource");

    }



    //gets the type of read permission the current authUser has
    //for the resource specified by details in $params
//    public function authorise($params, AuthUser $authUser)
//    {
//        foreach ($this->permissions as $permissionName => $permission) {
//
//            //check if authUser has a valid registered permission
//            if (!$permission->getObtainPermissionFunction()) {
//                if ($permissionName == $authUser->getPermission()) {
//                    $authUser->setPermission($permissionName);
//                    return $permissionName;
//                }
//            } //check if authUser matches criteria for this resource to gain new permission
//            else if (call_user_func_array($permission->getObtainPermissionFunction(), $params) == true) {
//                //authUser is authorised for this resource(resource described by params)
//                $authUser->setPermission($permissionName);
//                return $permissionName;
//            }
//        }
//
//        //does not have permission
//        throw new \UnauthorisedResourceException("user does not have read permissions for this resource");
//    }
}
