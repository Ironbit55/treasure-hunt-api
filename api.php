<?php

/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 08/11/2016
 * Time: 17:43
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use ResourceObjectMappers\ObjectToResourcePropertiesEncoder;
use DbObjects\Player;

use DatabaseAccessObjects\PlayerDAO;

use DbObjects\Treasure;
use DatabaseAccessObjects\TreasureDAO;
use Controllers\PlayerResourceController;
use DbObjects\ActiveTreasureHunt;
use DatabaseAccessObjects\ActiveTreasureHuntDAO;
use ResourceObjectMappers\ActiveTreasureHuntMapper;
use Controllers\ActiveTreasureHuntRC;


use Controllers\TreasureHuntTemplateResourceController;

use Controllers\TeamResourceController;

use ResourceObjectMappers\TeamMapper;
use DbObjects\Team;
use DatabaseAccessObjects\TeamDAO;


use DbObjects\Organiser;
use DatabaseAccessObjects\OrganiserDAO;
use Controllers\OrganiserResourceController;
use Resources\OrganiserResource;
use ResourceObjectMappers\OrganiserMapper;
use Controllers\BaseResourceController;

use DatabaseAccessObjects\FeedbackDAO;
use Controllers\ResourceController;

require_once 'resource_object_mappers/ObjectToResourcePropertiesEncoder.php';

require_once 'controllers/ResourceController.php';
require_once 'authorisation/AuthUser.php';
require_once 'controllers/BaseResourceController.php';
require_once 'db_objects/Team.php';
require_once 'db_objects/Player.php';
require_once 'database_access_objects/PlayerDAO.php';
require_once 'controllers/PlayerResourceController.php';
require_once 'controllers/TeamResourceController.php';
require_once 'resource_object_mappers/TeamMapper.php';
require_once 'db_objects/Organiser.php';
require_once 'database_access_objects/OrganiserDAO.php';
require_once 'controllers/OrganiserResourceController.php';
require_once 'db_objects/Treasure.php';
require_once 'database_access_objects/TreasureDAO.php';
require_once 'db_objects/ActiveTreasureHunt.php';
require_once 'database_access_objects/ActiveTreasureHuntDAO.php';
require_once 'controllers/ActiveTreasureHuntRC.php';
require_once 'resource_object_mappers/ActiveTreasureHuntMapper.php';
require_once 'controllers/TreasureHuntTemplateResourceController.php';
require_once 'resource_object_mappers/TreasureHuntTemplateMapper.php';
require_once 'resource_object_mappers/ActiveTreasureHuntMapper.php';
require_once 'database_access_objects/FeedbackDAO.php';
require_once 'middleware/JSON.php';
require_once 'middleware/token_auth.php';
include 'include/Db.php';
require_once 'include/PDO_db.php';
require 'vendor/autoload.php';


$c = new \Slim\Container();


/**
 * The internal architecture of the API can mostly be split into 3 layers
 *
 * Database Layer: handles interacting with the database - storing /
 *                  retrieving data as DbObjects
 *                  Consists of - DatabaseAccessObjects and DbObjects
 *
 * Resource Layer: handles representing data as resources.
 *                 The resource layer defines resources and their properties
 *                  A resource can be formed from valid data, e.g has the necessary properties, with acceptable values.
 *                 A resource controller handles manipulating a resource. Create/Read/ etc
 *                 The resource controller also defines who is authorised
 *                 to interact with the resource and exactly what permissions they have
 *                  Consists Of - Resources, ResourceControllers
 *
 * Mapping Layer: Maps between DbObjects and Resources.
 *                handles storing and getting data in resource form from the database
 *                A resource can be made of properties with data obtained from multiple different DbObjects (e.g CollectableTreasureResource)
 *                  Consists Of - ResourceObjectMappers
 *
 * Minor:
 * Authentication Layer: authentication middleware handles checking the authentication header in each request,
 * identifying who they are authenticating as and using that information to create an instance of AuthUser.
 *
 * Authorisation Layer: manages resource authorisation. Authorisation is a product of the currently authenticated user
 * and the resource they are trying to access. ResourcesControllers define who is authorised to interact with their resource.
 */



//custom exception handling, any exception if thrown and not handled
//will eventually end up here.
//Recognises the custom exceptions defined in include/Exceptions,
//forms and returns a response with the relevant http error status code
//with the message parsed as a property "message" in the body of the response.
$c['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        //401 Unauthorised resource
        if($exception instanceof UnauthorisedResourceException){
            $data['message'] = $exception->getMessage();
            return $c['response']->withStatus(401)
                ->withJson($data);
        }

        //404 Not Found,
        //we throw this mostly in the mapping layer when a
        //resource is requested that doesn't exist / can't be found in db
        if($exception instanceof ResourceNotFoundException){
            $data['message'] = $exception->getMessage();
            return $c['response']->withStatus(404)
                ->withJson($data);
        }

        //422 Unprocessable resource, mostly thrown in resource layer
        //when parsing user request bodies into a resource
        if($exception instanceof UnprocessableResourceException){
            $data['message'] = $exception->getMessage();
            return $c['response']->withStatus(422)
                ->withJson($data);
        }

        //409 Duplicate Resource
        //tried to create a resource that already exists
        //e.g username already exists
        //mostly thrown in resource layer
        if($exception instanceof DuplicateResourceException){
            $data['message'] = $exception->getMessage();
            return $c['response']->withStatus(409)
                ->withJson($data);
        }

        //403 Forbidden Resource
        if($exception instanceof ForbiddenResourceException){
            $data['message'] = $exception->getMessage();
            return $c['response']->withStatus(403)
                ->withJson($data);
        }



        //if the exception is not a custom one we defined it is thrown
        //as a 500 server error
        //debug setting to show stack trace on server error
        return $c['response']->withStatus(500)
            ->withHeader('Content-Type', 'text/html')
            ->write("message: " .$exception->getMessage() .
                    "\n". $exception->getTraceAsString());
    };
};

//debug setting to show error details
$c['config'] = [
    'settings' => [
        'displayErrorDetails' => 1,
    ],
];

$app = new \Slim\App($c);

$app->add(new Middleware\JSON('v1'));
//$app->add(new Middleware\token_auth(['/v1'], ['/v1/hello'], [['POST', '/v1/teams/.*/players'], ['POST', '/v1/teams/joinTeam'],
//    ['POST', '/v1/organisers'], ['POST', '/v1/organisers/login']]));

                                                                        //yea you bet i know regex
$app->add(new Middleware\token_auth(['/v1'], ['/v1/hello'], [['POST', '/v1/teams/.*/players']]));


$app->get('/hello/{name}', function (Request $request, Response $response) {

    $player = new Player();
    $player->name = "testName";
    $player->id = 9999;
    $name = $request->getAttribute('name');
    $attributeName = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");


});

$app->get('/v1/hello/{name}', function (Request $request, Response $response) {

    $player = new Player();
    $player->name = "testName";
    $player->id = 9999;
    $name = $request->getAttribute('name');
    $attributeName = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");
});

$app->get('/db/test', function (Request $request, Response $response) {

    $db = new Db();
    $mysqli = $db->connect();

    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    echo "database connection successful \n";
    echo $mysqli->host_info . "\n";

});



//organiser side of the api, organisers use this to login/signup, setup and deploy a treasure hunt
//a player will have nothing to do with any of this.

//starts with organiser as root resource
$app->group('/v1/organisers', function () {

    $this->get('', function (Request $request, Response $response) {
        $authUser = $request->getAttribute('auth_user');


        $baseResourceController = new BaseResourceController($authUser);

        $organiserCollection = $baseResourceController->getSubresourceCollectionRepresentation('organisers');


        return $response->withJson($organiserCollection, 200, JSON_PRETTY_PRINT);
    });

    //organiser signup
    $this->post('', function (Request $request, Response $response) {

        $parsedBody = $request->getParsedBody();
        $authUser = $request->getAttribute('auth_user');


        $baseController = new BaseResourceController($authUser);

        $organiserRC = $baseController->addSubresource('organisers', $parsedBody, false);
        $createdOrganiserController = $organiserRC->createResource();

        return $response->withJson($createdOrganiserController->getRepresentation() , 200, JSON_PRETTY_PRINT);

    });

    //organiser login
    $this->post('/login', function (Request $request, Response $response) {

        $parsedBody = $request->getParsedBody();
        $authUser = $request->getAttribute('auth_user');


        $baseController = new BaseResourceController($authUser);
        $organiserMapper = new OrganiserMapper();


        $organiserRC = $baseController->addSubresource('organisers', $parsedBody , false);
        $loggedInOrganiserController = $organiserRC->login();


        return $response->withJson($loggedInOrganiserController->getRepresentation() , 200, JSON_PRETTY_PRINT);

    });

    $this->group('/{organiser_id}', function () {
        $this->get('', function (Request $request, Response $response) {
            $authUser = $request->getAttribute('auth_user');
            //$username =  $request->getAttribute('username');
            $organiserId = $request->getAttribute('organiser_id');

            $organiserHelper = new OrganiserMapper();
            $organiserResource = $organiserHelper->getOrganiserResourceById($organiserId);


            $organiserRC = new OrganiserResourceController($authUser, $organiserResource, true);


            return $response->withJson($organiserRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
        });

        $this->group('/active_treasure_hunts', function () {

            $this->get('',function(Request $request, Response $response) {
                $authUser = $request->getAttribute('auth_user');
                $organiserId = $request->getAttribute('organiser_id');
                $organiserMapper = new OrganiserMapper();

                $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);

                $activeTreasureHuntsCollection = $organiserRC->getSubresourceCollectionRepresentation('active_treasure_hunts');

                return $response->withJson($activeTreasureHuntsCollection, 200, JSON_PRETTY_PRINT);
            });

            $this->post('',function(Request $request, Response $response) {
                $authUser = $request->getAttribute('auth_user');

                $organiserId = $request->getAttribute('organiser_id');
                $parsedBody = $request->getParsedBody();

                $organiserMapper = new OrganiserMapper();

                $organiserController = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);

                $activeTreasureHuntController =
                    $organiserController->addSubresource('active_treasure_hunts', $parsedBody, false);


                $createdActiveTreasureHuntController = $activeTreasureHuntController->createResource();

                return $response->withJson($createdActiveTreasureHuntController->getRepresentation(), 200, JSON_PRETTY_PRINT);
            });

            $this->group('/{active_treasure_hunt_id}', function () {
                $this->get('',function(Request $request, Response $response) {
                    $authUser = $request->getAttribute('auth_user');
                    $organiserId = $request->getAttribute('organiser_id');
                    $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');

                    $organiserMapper = new OrganiserMapper();

                    $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);



                    $activeTreasureHuntRC = $organiserRC->addActiveTreasureHuntById($activeTreasureHuntId);

                    return $response->withJson($activeTreasureHuntRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
                });

                $this->post('/start',function(Request $request, Response $response) {
                    $authUser = $request->getAttribute('auth_user');
                    $organiserId = $request->getAttribute('organiser_id');
                    $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');

                    $parsedBody = $request->getParsedBody();

                    checkAllowedProperties($parsedBody, ['use_dynamic_allocation']);
                    checkRequiredProperties($parsedBody, ['use_dynamic_allocation']);

                    $organiserMapper = new OrganiserMapper();

                    $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);



                    $activeTreasureHuntRC = $organiserRC->addActiveTreasureHuntById($activeTreasureHuntId);

                    $startedActiveTreasureHuntRC = $activeTreasureHuntRC->start($parsedBody['use_dynamic_allocation']);

                    return $response->withJson($startedActiveTreasureHuntRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
                });

                $this->post('/stop',function(Request $request, Response $response) {
                    $authUser = $request->getAttribute('auth_user');
                    $organiserId = $request->getAttribute('organiser_id');
                    $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');

                    $organiserMapper = new OrganiserMapper();

                    $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);



                    $activeTreasureHuntRC = $organiserRC->addActiveTreasureHuntById($activeTreasureHuntId);

                    $startedActiveTreasureHuntRC = $activeTreasureHuntRC->stop();

                    return $response->withJson($startedActiveTreasureHuntRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
                });

                $this->post('/teams', function (Request $request, Response $response) {
                    $authUser = $request->getAttribute('auth_user');
                    $organiserId = $request->getAttribute('organiser_id');
                    $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');

                    $parsedBody = $request->getParsedBody();

                    $organiserMapper = new OrganiserMapper();

                    $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);


                    $activeTreasureHuntRC = $organiserRC->addActiveTreasureHuntById($activeTreasureHuntId);

                    $teamRC = $activeTreasureHuntRC->addSubresource('teams', $parsedBody, false);
                    $createdTeam = $teamRC->createResource();


                    return $response->withJson($createdTeam->getRepresentation(), 200, JSON_PRETTY_PRINT);

                });
            });


        });

        $this->group('/treasure_hunt_templates', function () {

            $this->get('',function(Request $request, Response $response) {
                $authUser = $request->getAttribute('auth_user');
                $organiserId = $request->getAttribute('organiser_id');
                $organiserMapper = new OrganiserMapper();

                $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);

                $treasureHuntTemplatesCollection =
                    $organiserRC->getSubresourceCollectionRepresentation('treasure_hunt_templates');

                return $response->withJson($treasureHuntTemplatesCollection, 200, JSON_PRETTY_PRINT);
            });

            $this->post('',function(Request $request, Response $response) {
                $authUser = $request->getAttribute('auth_user');

                $organiserId = $request->getAttribute('organiser_id');
                $parsedBody = $request->getParsedBody();

                $organiserMapper = new OrganiserMapper();

                $organiserController = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);

                $treasureHuntController =
                    $organiserController->addSubresource('treasure_hunt_templates', $parsedBody, false);


                $createdTreasureHuntTemplateController = $treasureHuntController->createResource();

                return $response->withJson($createdTreasureHuntTemplateController->getRepresentation(), 200, JSON_PRETTY_PRINT);
            });

            $this->group('/{treasure_hunt_template_id}', function () {
                $this->get('',function(Request $request, Response $response) {
                    $authUser = $request->getAttribute('auth_user');
                    $organiserId = $request->getAttribute('organiser_id');
                    $treasureHuntTemplateId = $request->getAttribute('treasure_hunt_template_id');



                    $organiserMapper = new OrganiserMapper();

                    $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);

                    $treasureHuntTemplateRC = $organiserRC->addTreasureHuntTemplateById($treasureHuntTemplateId);


                    return $response->withJson($treasureHuntTemplateRC->getRepresentation(), 200, JSON_PRETTY_PRINT);

                });

                $this->group('/treasures', function () {
                    $this->get('',function(Request $request, Response $response) {
                        $authUser = $request->getAttribute('auth_user');
                        $organiserId = $request->getAttribute('organiser_id');
                        $treasureHuntTemplateId = $request->getAttribute('treasure_hunt_template_id');

                        $organiserMapper = new OrganiserMapper();

                        $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);

                        $treasureHuntTemplateRC = $organiserRC->addTreasureHuntTemplateById($treasureHuntTemplateId);


                        return $response->withJson($treasureHuntTemplateRC->getSubresourceCollectionRepresentation('treasures'), 200, JSON_PRETTY_PRINT);
                    });

                    $this->post('',function(Request $request, Response $response) {
                        $authUser = $request->getAttribute('auth_user');
                        $organiserId = $request->getAttribute('organiser_id');
                        $treasureHuntTemplateId = $request->getAttribute('treasure_hunt_template_id');

                        $parsedBody = $request->getParsedBody();

                        $organiserMapper = new OrganiserMapper();

                        $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);

                        $treasureHuntTemplateRC = $organiserRC->addTreasureHuntTemplateById($treasureHuntTemplateId);



                        $treasureRC = $treasureHuntTemplateRC->addSubresource('treasures', $parsedBody, false);


                        $createdTreasureRC = $treasureRC->createResource();

                        return $response->withJson($createdTreasureRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
                    });

                    $this->group('/{treasure_id}', function () {
                        $this->get('', function (Request $request, Response $response) {
                            $authUser = $request->getAttribute('auth_user');
                            $organiserId = $request->getAttribute('organiser_id');
                            $treasureHuntTemplateId = $request->getAttribute('treasure_hunt_template_id');
                            $treasureId = $request->getAttribute('treasure_id');

                            $organiserMapper = new OrganiserMapper();

                            $organiserRC = new OrganiserResourceController($authUser, $organiserMapper->getOrganiserResourceById($organiserId), true);

                            $treasureHuntTemplateRC = $organiserRC->addTreasureHuntTemplateById($treasureHuntTemplateId);

                            $treasureRC = $treasureHuntTemplateRC->addTreasureById($treasureId);

                            return $response->withJson($treasureRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
                        });

                    });


                });

            });


        });
    });
});


//this half of the api is more to do with the app. players, teams, collecting treasure.
//of course an organiser will also use this to
//to follow the progress of active treasure hunts they are running

//starts with active_treasure_hunt as root resource
$app->group('/v1/active_treasure_hunts', function () {


    $this->group('/{active_treasure_hunt_id}', function () {


        $this->get('', function (Request $request, Response $response) {

            $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');
            $authUser = $request->getAttribute('auth_user');

            $activeTreasureHuntMapper = new \ResourceObjectMappers\ActiveTreasureHuntMapper();
            $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true );

            return $response->withJson($activeTreasureHuntRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
        });

        $this->group('/teams', function () {
            $this->get('', function (Request $request, Response $response) {
                $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');
                $authUser = $request->getAttribute('auth_user');

                $activeTreasureHuntMapper = new ActiveTreasureHuntMapper();
                $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true );




                return $response->withJson($activeTreasureHuntRC->getSubresourceCollectionRepresentation('teams'), 200, JSON_PRETTY_PRINT);
            });

            $this->post('', function (Request $request, Response $response) {
                $authUser = $request->getAttribute('auth_user');
                $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');

                $parsedBody = $request->getParsedBody();


                $activeTreasureHuntMapper = new \ResourceObjectMappers\ActiveTreasureHuntMapper();
                $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true );

                $teamRC = $activeTreasureHuntRC->addSubresource('teams', $parsedBody, false);
                $createdTeam = $teamRC->createResource();


                return $response->withJson($createdTeam->getRepresentation(), 200, JSON_PRETTY_PRINT);

            });


            $this->group('/{team_id}', function () {
                $this->get('', function (Request $request, Response $response) {
                    $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');
                    $teamId = $request->getAttribute('team_id');
                    $authUser = $request->getAttribute('auth_user');

                    $activeTreasureHuntMapper = new \ResourceObjectMappers\ActiveTreasureHuntMapper();
                    $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true );

                    $teamRC = $activeTreasureHuntRC->addTeam($teamId);



                    return $response->withJson($teamRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
                });

                $this->post('/collect_treasure', function (Request $request, Response $response) {
                    $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');
                    $teamId = $request->getAttribute('team_id');
                    $authUser = $request->getAttribute('auth_user');
                    $parsedBody = $request->getParsedBody();

                    checkAllowedProperties($parsedBody, ['latitude', 'longitude', 'qr_code' ]);
                    checkRequiredProperties($parsedBody, ['latitude', 'longitude', 'qr_code' ]);

                    $latitude = $parsedBody['latitude'];
                    $longitude = $parsedBody['longitude'];
                    $qr_code = $parsedBody['qr_code'];

                    $activeTreasureHuntMapper = new \ResourceObjectMappers\ActiveTreasureHuntMapper();
                    $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true );

                    $teamResourceController = $activeTreasureHuntRC->addTeam($teamId);


                    $collectableTreasureRC = $teamResourceController->addCurrentCollectableTreasure();
                    $collectedTreasureRC = $collectableTreasureRC->collectTreasure($latitude, $longitude, $qr_code);


                    return $response->withJson($collectedTreasureRC->getRepresentation(), 201, JSON_PRETTY_PRINT);
                });

                $this->post('/hot_or_cold', function (Request $request, Response $response) {
                    $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');
                    $teamId = $request->getAttribute('team_id');
                    $authUser = $request->getAttribute('auth_user');
                    $parsedBody = $request->getParsedBody();

                    checkAllowedProperties($parsedBody, ['latitude', 'longitude']);
                    checkRequiredProperties($parsedBody, ['latitude', 'longitude']);

                    $latitude = $parsedBody['latitude'];
                    $longitude = $parsedBody['longitude'];


                    $activeTreasureHuntMapper = new \ResourceObjectMappers\ActiveTreasureHuntMapper();
                    $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true );

                    $teamResourceController = $activeTreasureHuntRC->addTeam($teamId);


                    $collectableTreasureRC = $teamResourceController->addCurrentCollectableTreasure();
                    $hotOrColdResponse = $collectedTreasureRC = $collectableTreasureRC->hotOrCold($latitude, $longitude);


                    return $response->withJson($hotOrColdResponse, 201, JSON_PRETTY_PRINT);
                });

                $this->get('/collectable_treasures', function (Request $request, Response $response) {
                    $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');
                    $teamId = $request->getAttribute('team_id');
                    $authUser = $request->getAttribute('auth_user');

                    $activeTreasureHuntMapper = new \ResourceObjectMappers\ActiveTreasureHuntMapper();
                    $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true);

                    $teamRC = $activeTreasureHuntRC->addTeam($teamId);

                    return $response->withJson($teamRC->getSubresourceCollectionRepresentation('collectable_treasures'), 200, JSON_PRETTY_PRINT);
                });

                $this->group('/players', function () {
                    $this->get('', function (Request $request, Response $response) {
                        $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');
                        $teamId = $request->getAttribute('team_id');
                        $authUser = $request->getAttribute('auth_user');

                        $activeTreasureHuntMapper = new \ResourceObjectMappers\ActiveTreasureHuntMapper();
                        $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true );

                        $teamRC = $activeTreasureHuntRC->addTeam($teamId);



                        return $response->withJson($teamRC->getSubresourceCollectionRepresentation('players'), 200, JSON_PRETTY_PRINT);
                    });
                    $this->get('/{player_id}', function (Request $request, Response $response) {
                        $activeTreasureHuntId = $request->getAttribute('active_treasure_hunt_id');
                        $teamId = $request->getAttribute('team_id');
                        $playerId = $request->getAttribute('player_id');
                        $authUser = $request->getAttribute('auth_user');

                        $activeTreasureHuntMapper = new \ResourceObjectMappers\ActiveTreasureHuntMapper();
                        $activeTreasureHuntRC = new ActiveTreasureHuntRC($authUser, $activeTreasureHuntMapper->getResource($activeTreasureHuntId), true );

                        $teamRC = $activeTreasureHuntRC->addTeam($teamId);
                        $playerRC = $teamRC->addPlayer($playerId);


                        return $response->withJson($playerRC->getRepresentation(), 200, JSON_PRETTY_PRINT);
                    });

                });
            });
        });
    });
});




//this group is used so a player can join a team and identify the active_treasure_hunt they belong to
//whilst only requiring a team code to join.
$app->group('/v1/teams', function () {

    //creates a new player belonging the team specified by the unique 4 digit public team code
    $this->post('/jointeam', function (Request $request, Response $response) {
        $authUser = $request->getAttribute('auth_user');
        $parsedBody = $request->getParsedBody();


        if (!array_key_exists('public_team_code', $parsedBody)) {
            throw new UnprocessableResourceException('request body did not contain \"public_team_code\" property');
        }
        $publicTeamCode = $parsedBody['public_team_code'];
        unset($parsedBody['public_team_code']);


        $teamMapper = new TeamMapper();
        $timeBefore = microtime();
        $teamRC = new TeamResourceController($authUser, $teamMapper->getResourceByPublicTeamCode($publicTeamCode), true);

        $playerRC = $teamRC->addSubresource('players', $parsedBody, false);
        $createdPlayerRC = $playerRC->createResource();

//        $timeTaken = $timeBefore - microtime();
//        echo $timeTaken;
//        die();
        //set url redirect header to point to url of newly created player resource
        //$responseWithRedirect = $response->withRedirect("/v1/teams/$team->id/players/" . $player->id);

        //return player we just created as json object in body
        return $response->withJson($createdPlayerRC->getRepresentation(), 201, JSON_PRETTY_PRINT);

    });

    $this->get('/{team_id}', function (Request $request, Response $response) {
            $authUser = $request->getAttribute('auth_user');
            $teamId = $request->getAttribute('team_id');

            $teamMapper = new TeamMapper();
            $teamResourceController =
                new TeamResourceController($authUser, $teamMapper->getResourceByTeamId($teamId), true);

            return $response->withJson($teamResourceController->getRepresentation(), 201, JSON_PRETTY_PRINT);
    });
});


//submit feedback endpoint
//this doesn't use our resource system
//becsase that would be a bit overkill
$app->post('/v1/feedback', function (Request $request, Response $response) {
    $authUser = $request->getAttribute('auth_user');
    $parsedBody = $request->getParsedBody();

    $FEEDBACK_SUBMIT_SCORE_INCREMENT = 2;
    //only a player or admin can submit feedback
    if(!$authUser->isPlayer() && !$authUser->isAdmin()){
        throw new UnauthorisedResourceException("do not have permission to access this resource");
    }


    checkRequiredProperties($parsedBody, ["active_treasure_hunt_id", "player_token", "name", "question_2", "question_3", "question_4", "question_5", "question_6", "question_7", "question_8",
        "question_9", "question_10", "question_11", "question_12", "question_13", "question_14",]);
    checkRequiredProperties($parsedBody, ["active_treasure_hunt_id", "player_token", "name", "question_2", "question_3", "question_4", "question_5", "question_6", "question_7", "question_8",
        "question_9", "question_10", "question_11", "question_12", "question_13", "question_14",]);

    $feebackDAO = new \DatabaseAccessObjects\FeedbackDAO();
    $feedback = null;

    //check whether play has already submitted feedback or not by checking whether
    //feedback with this player token already exists in table
    try {

        $feedback = $feebackDAO->createFeedback($parsedBody);
    } catch (\PDOException $e) {
        if ($e->errorInfo[1] == MYSQL_CODE_DUPLICATE_KEY) {
            //The INSERT query failed due to a key constraint violation.
            //player has already submitted
           throw new DuplicateResourceException("This player has already submitted feedback");
        } else {
            //its a different type of PDO exception which we still want to throw
            throw $e;
        }
    }
    if($feedback != null) {
        if ($authUser->isPlayer()) {
            $player = $authUser->getUserObject();
            $teamDAO = new TeamDAO();
            $teamDAO->updateScoreOnFeedbackSubmit($player->team_id, $FEEDBACK_SUBMIT_SCORE_INCREMENT);
        }
    }


    return $response->withJson($feedback, 201, JSON_PRETTY_PRINT);
});

//get feedback endpoint
//this doesn't use our resource system
//becsase that would be a bit overkill
$app->get('/v1/feedback', function (Request $request, Response $response) {
    $authUser = $request->getAttribute('auth_user');

    //only admin can view feedback
    if(!$authUser->isAdmin()){
        throw new UnauthorisedResourceException("do not have permission to access this resource");
    }
    $feedbackDAO = new \DatabaseAccessObjects\FeedbackDAO();
    $allFeedback = $feedbackDAO->getAllFeedback();


    return $response->withJson($allFeedback, 201, JSON_PRETTY_PRINT);
});










//this is also in DAO but i thought it would be usefull here for checking user input
//that doesn't get parsed into an actual resource, like when collecting a treasure
function checkAllowedProperties(array $associativeArray, array $allowedParams){
    foreach($associativeArray as $coloumnName => $columnValue){
        if(!in_array($coloumnName, $allowedParams)){
            throw new UnprocessableResourceException("request body contained unknown property \"$coloumnName\"");
        }
    }
}

function checkRequiredProperties(array $associativeArray, array $requiredParams){
    foreach($requiredParams as $requiredParam){
        if(!array_key_exists($requiredParam, $associativeArray)){
            throw new UnprocessableResourceException("request body missing required property \"$requiredParam\"");
        }
    }
}



$app->run();
