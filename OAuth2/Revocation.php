<?php namespace AuthExtension\OAuth2;

use CodeIgniter\HTTP\ResponseInterface;
use OAuth2\Request;
use OAuth2\Response;

class Revocation {

    public static function handle(ResponseInterface $response): void {
        $request = Request::createFromGlobals();
        $oauthResponse = new Response();
        ServerLib::getInstance()->server->handleRevokeRequest($request, $oauthResponse);

        $response->setStatusCode($oauthResponse->getStatusCode());
        $response->setJSON($oauthResponse->getResponseBody());
        $response->send();
    }

}
