<?php namespace AuthExtension\OAuth2;

use CodeIgniter\HTTP\Response;
use OAuth2\Request;

/**
 * Class Revocation
 * @package AuthExtension\OAuth2
 */
class Revocation {

    /**
     * @param Response $response
     */
    public static function handle($response) {
        $request = Request::createFromGlobals();
        $oauthResponse = new \OAuth2\Response();
        ServerLib::getInstance()->server->handleRevokeRequest($request, $oauthResponse);

        $response->setStatusCode($oauthResponse->getStatusCode());
        $response->setJSON($oauthResponse->getResponseBody());
        $response->send();
    }

}