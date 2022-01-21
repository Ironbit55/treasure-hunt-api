<?php
/**
 * JSON
 *
 * This middleware ensures JSON encoded responses and request bodies
 *
*/

namespace Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class JSON
{
    public function __construct($root = '')
    {
        $this->root = $root;
    }
    /**
     * Call the middleware
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next){

        if (preg_match(
            '|^' . $this->root . '.*|',
            $request->getUri()->getPath()

        )) {

            $method = strtolower($request->getMethod());
            $mediaType = $request->getMediaType();
            if (in_array(
                    $method,
                    array('post', 'put', 'patch')
                ) && '' !== $request->getBody()
            ) {

                if (empty($mediaType)
                    || $mediaType !== 'application/json'
                ) {
                    //media type is mot JSON so return error status and exit
                    return $response->withStatus(415);


                }
            }

            $response = $next($request, $response);

//        $headerValueArray = $response->getHeader('Content-Type');
//        if(strpos($headerValueArray[0],'application/json') === false){
//
//            return $response->withStatus(415);
//        }
//        return $response;

            //force response headers to JSON
            $newResponse = $response->withHeader('Content-type', 'application/json');

            return $newResponse;
        }

        //url path doesn't match one we are interested in so let it through as normal
        return $next($request, $response);
    }

}