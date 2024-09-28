<?php namespace AuthExtension\OAuth2;

use CodeIgniter\HTTP\ResponseInterface;
use OAuth2\Request;

class Token {

    public static function handle(ResponseInterface $response): void {
        /** @var \OAuth2\Response $oauthResponse */
        $oauthResponse = ServerLib::getInstance()->server->handleTokenRequest(Request::createFromGlobals());

        $response->setStatusCode($oauthResponse->getStatusCode());
        $response->setJSON($oauthResponse->getResponseBody());
        $response->send();
    }

}
