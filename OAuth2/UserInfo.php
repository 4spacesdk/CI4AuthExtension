<?php namespace AuthExtension\OAuth2;

use AuthExtension\AuthExtension;
use CodeIgniter\HTTP\ResponseInterface;
use OAuth2\Request;
use OAuth2\Response;

class UserInfo {

    public static function handle(ResponseInterface $response): void {
        $request = Request::createFromGlobals();
        $oauthResponse = new Response();

        // Browsers preflight the request to look for CORS headers.
        // If the request is acceptable, then they will send the real request.
        $bearer = $request->headers('Authorization');
        if (is_null($bearer)) {
            $response->setJSON([]);
            $response->send();
            return;
        }

        // OAuth 2.0 authentication: "openid" scope.
        if (!ServerLib::getInstance()->server->verifyResourceRequest($request, $oauthResponse, 'openid')) {
            $response->setStatusCode($oauthResponse->getStatusCode());
            $response->setJSON($oauthResponse->getResponseBody());
            $response->send();
            return;
        }

        $token = ServerLib::getInstance()->server->getAccessTokenData($request, $oauthResponse);

        // The default behavior is to use "username" as user_id.
        $user = AuthExtension::authorize();
        // Groups of claims are returned based on the requested scopes.
        // Scopes with matching claims: profile, email, address, phone.
        // http://openid.net/specs/openid-connect-core-1_0.html#ScopeClaims
        $claims = ServerLib::getInstance()->storage->getUserClaims($user['username'], $token['scope']);

        // The sub Claim MUST always be returned in the UserInfo Response.
        // http://openid.net/specs/openid-connect-core-1_0.html#UserInfoResponse
        $claims += array(
            'sub' => $token['user_id'],
        );

        // Custom claims.
        // Groups.
        //$groups = $this->ion_auth->groups()->result_array();
        $claims += array(
            'groups' => []
        );
        $oauthResponse->setParameters($claims);

        $response->setStatusCode($oauthResponse->getStatusCode());
        $response->setJSON($oauthResponse->getResponseBody());
        $response->send();
    }

}
