<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 14/04/17
 * Time: 14:53
 */


namespace Controllers;

use Authorisation\AuthUser;
use Authorisation\PermissionsPolicy;
use Resources\Resource;

require_once 'authorisation/PermissionsPolicy.php';
require_once 'authorisation/AuthUser.php';
require_once 'resources/Resource.php';
require_once 'include/Exceptions.php';

/*
 * represents a subresource collection
 * as a collection of resourceController instances of the relevant resource
 * when creating a resource collection the resource controller defining it
 * must define a function to form a resourceController from the properties
 * of the subresource
 * and a function that returns a collection of subresource data from the db that belongs to
 * the parent resource.
 */
class SubresourceCollection{
    private $name;
    private $resourceCollection = array();
    private $getResourceCollectionFunction;
    private $addResourceFunction = array();

    function __construct($name, $getResourceCollectionFunction, $addResourceFunction)
    {
        $this->name = $name;
        $this->getResourceCollectionFunction = $getResourceCollectionFunction;
        $this->addResourceFunction = $addResourceFunction;
    }

    public function getResourceCollectionFromDatabase(){
        return call_user_func($this->getResourceCollectionFunction);
    }

    public function setResourceCollectionFromDatabase(){
        $this->setResourceCollection(call_user_func($this->getResourceCollectionFunction));
    }

    public function getResourceCollection(){
        return $this->resourceCollection;
    }

    //add a subresource controller to the collection from resource data
    //the resource data will be converted to the correct
    //instance of a resourceController
    //when it is passed through the addResourceFunction
    public function addResource($resourceData, $fromDatabase){
        $resourceController = null;
        try {
            $resourceController = call_user_func_array($this->addResourceFunction, array($resourceData, $fromDatabase));
            $this->resourceCollection[] = $resourceController;
        }catch(\ResourceNotFoundException $e){
           throw $e;
        }

        return $resourceController;
    }

    //set the subresource controllers collection directly
    public function setResourceCollection($resourceCollection){
        $this->resourceCollection = $resourceCollection;
    }


}

/**
 * handles interacting with a resource. possible operations that can be made on the resource depend on permissions
 * requesting user has in relation to the resource
 * permissions are set by the ResourceController for each resource
 *
 * @author Edward Curran
 */
abstract class ResourceController {
    protected $authUser;

    protected $permissionsPolicy;

    protected $resource;

    protected $authParams = [];

    protected $isAuthParamsSet = false;

    protected $fromDatabase;

    protected $databaseResourceParams;

    private $subresources = array();

    private $subresourceNesting = true;


    //protected $params;

    function __construct(AuthUser $authUser, Resource $resource, $fromDatabase, $requiredDatabaseResourceProperties, $requiredResourceAuthProperties){
        //its important we clone the authUser here,
        //the permissions we give it only belong in the scope of a specific resource

        $this->authUser = clone $authUser;
        $this->resource = $resource;
        //$this->authUser = clone $authUser;
        $this->permissionsPolicy = new PermissionsPolicy();
        $this->fromDatabase = $fromDatabase;

        $this->setDatabaseResourceParams($requiredDatabaseResourceProperties, $fromDatabase);
        $this->setAuthParamsFromResource($requiredResourceAuthProperties);
        //$this->resource = $resource;

    }

    protected function mustBeFromDatabase(){
        if(!$this->fromDatabase){
            throw new \BadMethodCallException("can't call method on resource not from database");
        }

    }


    private function setDatabaseResourceParams(array $requiredResourceProperties, $fromDatabase){
        if($fromDatabase) {
            foreach ($requiredResourceProperties as $requiredResourceProperty) {

                if (!$this->resource->propertyIsSet($requiredResourceProperty)) {
                    throw new \InvalidArgumentException("resource is said to be from database but does not contain property \"$requiredResourceProperty\"");
                }
                $resourcePropertyValue = $this->resource->getProperty($requiredResourceProperty);
                $this->databaseResourceParams[$requiredResourceProperty] = $resourcePropertyValue;
            }
        }
        if(!$fromDatabase){
            foreach ($requiredResourceProperties as $requiredResourceProperty) {

                if ($this->resource->getProperty($requiredResourceProperty)) {

                    throw new \UnprocessableResourceException("can't set property: \"$requiredResourceProperty\"");
                }
            }
        }
    }

    private function setAuthParamsFromResource($propertyNames)
    {
        $this->authParams = [];
        //might want to return false if resource is not from database.
        foreach ($propertyNames as $propertyName) {

            if (!$this->resource->propertyIsSet($propertyName)) {
                //resource does not contain required auth property
                $this->isAuthParamsSet = false;
                return;
            }
            $resourcePropertyValue = $this->resource->getProperty($propertyName);
            $this->authParams[] = $resourcePropertyValue;

        }
        $this->isAuthParamsSet = true;
    }

    protected function setSubresourceNesting($boolean){
        $this->subresourceNesting = $boolean;
    }

    private function gainPermission(){
        if($this->isAuthParamsSet) {

            $permissionGained = $this->permissionsPolicy->gainPermission($this->authParams, $this->authUser);

            return $permissionGained;
            //$this->authUser->setPermission($this->permissionsPolicy->gainPermission($this->authParams, $this->authUser));
        }
    }

    protected function authorise(){

        $this->gainPermission();

        $permission = $this->permissionsPolicy->authorise($this->authUser);

        return $permission;
    }

    protected function defineSubresource($subresourceName, $getResourceCollectionFunction, $addResourceFunction){
        if(!$this->resource->isValidProperty($subresourceName)){
            throw new \InvalidArgumentException("could not define subresource \"$subresourceName\"" .
                "as the resource does not contain a property with that name");
        }

        $this->subresources[$subresourceName] = new SubresourceCollection($subresourceName, $getResourceCollectionFunction, $addResourceFunction);

    }

    public function getSubresource($subresourceName){
        if(!array_key_exists($subresourceName, $this->subresources)) {
            throw new \InvalidArgumentException("subresource with name: \"$subresourceName\" could not be found");
        }
        return $this->subresources[$subresourceName];
    }

    /**
     * @param string $subresourceName
     * @param array $value
     * set a subresource collection from an array of ResourceControllers
     */
    protected function setSubresource($subresourceName, array $subresourceCollection){
        $this->getSubresource($subresourceName)->setResourceCollection($subresourceCollection);

    }

    //set a subresource collection from children of parent found in database
    protected function setSubresourcesFromDatabase($subresourceName){
        $subresource = $this->getSubresource($subresourceName);

        //get a collection of this subresource from database
        //and add it to the relevent subresource
        $this->addSubresourceCollection($subresourceName,
            $subresource->getResourceCollectionFromDatabase(), true );
    }

    //add a resource to a subresource collection from resource data
     public function addSubresource($subresourceName, $subresourceData, $fromDatabase){
         $this->authorise();
         return $this->getSubresource($subresourceName)->addResource($subresourceData, $fromDatabase);
    }



    //set a subresource collection from any instances of that subresource found in the resource
    //or from child resources from database
    //when a resource controller is created using a resource containing subresource data
    //this data is parsed here into the correct resource controller and added to the correct subresource collection
    //if a resourceController is from database we will also get any instances of child resources from the database
    //and add that to the subresource collection
    private function setSubresourceCollection($subresourceName){
        $subresource = $this->getSubresource($subresourceName);
        $permission = $this->authorise();

        if(!in_array($subresourceName, $permission->getReadAttributes())){
            //do not have permission to view this subresource
            throw new \UnauthorisedResourceException("do not have permission to view subresource: \"$subresourceName\"");
        }

        $resourceData = $this->resource->getPropertiesSubset($permission->getReadAttributes());


        if(array_key_exists($subresourceName, $resourceData)){
            //resource contains data for this subresource


            //add subresource data to subresource collection
            $this->addSubresourceCollection($subresourceName,  $resourceData[$subresourceName], false );
        }


        //get subresources from database if this resource(the parent resource) is from database
        //might not want to do this automatically here
        if($this->fromDatabase) {
            $this->setSubresourcesFromDatabase($subresourceName);
        }
    }

    //get a representation of the resource which shows
    //only the attributes the the current users permission level authorises them to view

    public function getRepresentation()
    {
        $permission = $this->authorise();

        //we get a view showing only the properties of this resource we have permission
        //to view
        $resourceData = $this->resource->getPropertiesSubset($permission->getReadAttributes());


        /*
         * populate the resources subresources, if they are a property we have permission to view
         */
        foreach($this->subresources as $subresourceName => $subresourceValue){

            if(in_array($subresourceName, $permission->getReadAttributes())) {
                //we have permission to view this subresource
                if($this->subresourceNesting) {
                    $resourceData[$subresourceName] = $this->getSubresourceCollectionRepresentation($subresourceName);
                }else{
                    $resourceData[$subresourceName] = $this->getSubresourceCollectionUriRepresentation($subresourceName);
                }
            }
        }

        //return the view including subresources
        return $resourceData;

    }

    public function getUriRepresentation(){
        $permission = $this->authorise();

        return array("uri" => $this->getUri());
    }
    //gets an array containing representations of the specified subresource
    //belonging to this resource

    public function getSubresourceCollectionRepresentation($subresourceName){
        $subresource = $this->getSubresource($subresourceName);

        //set the subresource collection from any subresource data found in the resource
        //or from child resources from database
        $this->setSubresourceCollection($subresourceName);

        $subResourceCollectionRepresentation = array();
        foreach($subresource->getResourceCollection() as $resourceController){
            $subResourceCollectionRepresentation[] = $resourceController->getRepresentation();
        }

        return $subResourceCollectionRepresentation;

    }

    public function getSubresourceCollectionUriRepresentation($subresourceName){
        $this->setSubresourceCollection($subresourceName);

        $subresource = $this->getSubresource($subresourceName);

        $subResourceCollectionUriRepresentation = array();
        foreach($subresource->getResourceCollection() as $resourceController){
            $parentUri = $this->getUri();
            $uriKeyValue = $resourceController->getUriRepresentation();
            $uriKeyValue['uri'] = $parentUri . $uriKeyValue['uri'];
            $subResourceCollectionUriRepresentation[] = $uriKeyValue;
        }

        return $subResourceCollectionUriRepresentation;

    }

    //store this resource in the database, only stores the properties which


    //store the resource in database
    //including any nested subresources
    public function createResource(){

        if($this->fromDatabase){
            return new \BadMethodCallException("can't create resource already from database");
        }
        $permission = $this->authorise();



        //ToDo: if the resource does contain properties the user is not allowed to creat then this should probably fail and return an error
        //at the moment it stores the properties its allowed to and ignores the rest
        $authorisedResourceProperties = $this->resource->getPropertiesSubset($permission->getCreateAttributes());


        foreach($this->resource->getProperties() as $resourceName => $resourceValue){
            if(!in_array($resourceName, $permission->getCreateAttributes())){
                //the current resource contains a property it does not have permission to create
                //so we return for resource creation failed
                throw new \UnauthorisedResourceException("user does not have permission to create resource with property: \"$resourceName\"");
            }
        }


        $createdResourceController = $this->storeResource($authorisedResourceProperties);


        /*
         * create nested subresources!
         * might not want to allow this to be possible but its kinda fun.
        */

        //stores multiple collections of subresource data
        $subresourceCollections = array();


        //fill $subresourceCollections for each defined subresource with data found in the resource
        foreach($authorisedResourceProperties as $resourcePropertyName => $resourcePropertyValue){
            if(array_key_exists($resourcePropertyName, $this->subresources)){
                //property is a subresource
                //add the property data to the correct subresource collection
                $subresourceCollections[$resourcePropertyName] = $resourcePropertyValue;
            }
        }
        //foreach subresource collection add it too our newly created resource
        //and then create the collection (to store it in database)
        foreach($subresourceCollections as $subresourceName => $subresourceCollection){
            $createdResourceController->addSubresourceCollection($subresourceName, $subresourceCollection, false);
            $createdResourceController->createSubresourceCollection($subresourceName);
        }


        return $createdResourceController;
        //return $this->storeResource($authorisedResourceProperties);
    }
    public function addSubresourceCollection($subresourceName, $subresourceCollection, $fromDatabase){

        if(is_array($subresourceCollection)) {
            foreach ($subresourceCollection as $resourceProperties) {
                try {
                    $this->addSubresource($subresourceName, $resourceProperties, $fromDatabase);
                }catch(\UnauthorisedResourceException $e){
                    ///we aren't authorised for this specific resource, so just continue
                    //we may be authorised for others in the collection
                }
            }
        }else {
            try {
                $this->addSubresource($subresourceName, $subresourceCollection, $fromDatabase);
            }catch(\UnauthorisedResourceException $e){
                ///we aren't authorised for this specific resource, so just continue


            }

        }

    }

    public function addSubresourceCollectionFromDatabase(){

    }

    public function createSubresourceCollection($subresourceName){
        if(!$this->fromDatabase){
            return new \BadMethodCallException("can't create subresources of resource not from database");
        }

        $resourceControllerCollection = $this->getSubresource($subresourceName)->getResourceCollection();
        $createdResourceControllerCollection = array();
        foreach($resourceControllerCollection as $resourceController){
            $createdResourceControllerCollection[] = $resourceController->createResource();
        }

        $this->setSubresource($subresourceName, $createdResourceControllerCollection);
    }

    /**
     * Each resource controller is responsible for implementing this
     * To implement storing properties of the resource it controls
     * to the database
     * @param array $resourceProperties
     * @return ResourceController
     */
    protected abstract function storeResource(array $resourceProperties);

    /**
     * return the uri of the resource implementing this
     */
    protected abstract function getUri();


    public function setChildParent($childResourceData, $childParentKeyName, $parentKeyValue)
    {
        //child already has parent key set
        if (array_key_exists($childParentKeyName, $childResourceData)) {
            if ($childResourceData[$childParentKeyName] != $parentKeyValue) {
                //parent key does not match that of the parent we are trying to set
                throw new \ResourceNotFoundException("resource is not a child of specified parent");
            }
        } else {
            //set the childs parent key to the valid given
            $childResourceData[$childParentKeyName] = $parentKeyValue;
        }

        return $childResourceData;

    }


}