<?php
/**
 * Created by PhpStorm.
 * User: Edward
 * Date: 07/03/2017
 * Time: 14:54
 */
/*
 * middleware to authenticate php_auth_token found in header
 *
 * path argument specifies path roots which require authorisation
 * eg "/api" means every path starting with /api will require authentication
 *
 * passthroughs allow you to define explicit exceptions to the path parameter
 * eg "api/token" will allow only the api/token path to not be authenticated
 * api/token/somethingelse would still be authenticated
 *
 * methodPassthrough is the same as passthrough except you can specify a specific
 * HTTP verb to allow through on that path without authentication
 * each methodPassthrough is an array with the first element being the HTTP method and the second the path
*/
namespace Middleware;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use DbObjects\Player;
use DatabaseAccessObjects\PlayerDAO;
use DbObjects\Organiser;
use DatabaseAccessObjects\OrganiserDAO;
use Authorisation\AuthUser;
use Authorisation\AuthUserType;



require_once 'db_objects/Player.php';
require_once 'authorisation/AuthUser.php';
require_once 'database_access_objects/PlayerDAO.php';
require_once 'db_objects/Organiser.php';
require_once 'database_access_objects/OrganiserDAO.php';
include_once 'include/PDO_db.php';

class token_auth
{
    public function __construct($path = ["/"], $passthrough = [], $methodPassthrough = [])
    {
        $this->path = $path;
        $this->passthrough = $passthrough;
        $this->methodPassthrough = $methodPassthrough;
    }
    /**
     * Call the middleware
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate($request)) {
            return $next($request, $response);
        }

        //if token cannot be found then return we give the user base_access
        if(!$token = $this->fetchToken($request)){
//            $response->getBody()->write("could not find Php-Auth-Token in header");
//            return $response->withStatus(401);
            $request = $request->withAttribute('auth_user', new AuthUser(AuthUserType::BaseUser, null));
            return $next($request, $response);
        }


        if($player = $this->verifyPlayer($token)){
            //token verifies user as a player
            $request = $request->withAttribute('auth_user', new AuthUser(AuthUserType::Player, $player));
            return $next($request, $response);
        }

        if($organiser = $this->verifyOrganiser($token)){
            //token verifies user as an organiser
            $request = $request->withAttribute('auth_user', new AuthUser(AuthUserType::Organiser, $organiser));
            return $next($request, $response);
        }
        if($token == "super_secret"){
            $request = $request->withAttribute('auth_user', new AuthUser(AuthUserType::Admin, null));
            return $next($request, $response);
        }



        throw new \UnauthorisedResourceException("token could not be verified");

    }

    private function shouldAuthenticate(RequestInterface $request){
        $uri = "/" . $request->getUri()->getPath();
        $uri = preg_replace("#/+#", "/", $uri);
        $method = strtolower($request->getMethod());

        /* If request path and method matches method passthrough path and method we should not authenticate. */
        foreach ($this->methodPassthrough as $methodPassthrough) {
            if($method == strtolower($methodPassthrough[0])){
                $passthrough = rtrim($methodPassthrough[1], "/");
                if (!!preg_match("@^{$passthrough}$@", $uri)) {
                    return false;
                }
            }
        }

        /* If request path matches passthrough should not authenticate. */
        foreach ($this->passthrough as $passthrough) {

            $passthrough = rtrim($passthrough, "/");
            if (!!preg_match("@^{$passthrough}$@", $uri)) {
                return false;
            }
        }
        /* Otherwise check if path matches and we should authenticate. */
        foreach ($this->path as $path) {

            $path = rtrim($path, "/");
            if (!!preg_match("@^{$path}(/.*)?$@", $uri)) {
                return true;
            }
        }


        return false;
    }

    private function fetchToken(RequestInterface $request){
        foreach($request->getHeader('Php-Auth-Token') as $headerArg){
            if($token = $headerArg){
                return $token;
            }
        }
        return null;
    }

    private function verifyPlayer($token){
        $playerDAO = new PlayerDAO();
        $player = $playerDAO->getPlayerByToken($token);

        if($player){
            //player matching token succesfully found
            return $player;
        }
        //sql query did not find a player matching the token
        return null;
    }

    private function verifyOrganiser($token){
        $organiserDAO = new OrganiserDAO();

        if($organiser = $organiserDAO->getOrganiserByToken($token)){
            if(time() < strtotime($organiser->token_expire)){
                return $organiser;
            }
        }
        return null;
    }


    private function verify($token, RequestInterface $request){

       if($player = $this->verifyPlayer($token)){
           //token verifies user as a player
           echo ('token authorised as Player: ' . $player->name);

           $request = $request->withAttribute('auth_player', $player);
           return true;
       }

       if($organiser = $this->verifyOrganiser($token)){
            //token verifies user as an organiser
           $request = $request->withAttribute('auth_organiser', $organiser);
            return true;
       }

        return false;

    }
}