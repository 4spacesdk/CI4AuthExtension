<?php namespace AuthExtension\OAuth2;

use AuthExtension\AuthExtension;
use OAuth2\Request;
use OAuth2\Response;

/**
 * Class Token
 * @package AuthExtension\OAuth2
 */
class Token {

    /**
     * @param \CodeIgniter\HTTP\Response $response
     */
    public static function handle($response) {
        /** @var Response $oauthResponse */
        $oauthResponse = ServerLib::getInstance()->server->handleTokenRequest(Request::createFromGlobals());

        $response->setStatusCode($oauthResponse->getStatusCode());
        $response->setJSON($oauthResponse->getResponseBody());
        $response->send();
    }

}