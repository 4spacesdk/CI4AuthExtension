<?php namespace AuthExtension\OAuth2;

use AuthExtension\Entities\OAuthScope;
use AuthExtension\Models\OAuthScopeModel;
use CodeIgniter\HTTP\ResponseInterface;
use DebugTool\Data;
use SimpleJWT\Keys\RSAKey;

class OpenIdConfiguration {

    public static function handle(ResponseInterface $response): void {
        $baseUrl = ServerLib::getInstance()->server->getConfig('issuer');

        $arr = [
            'issuer' => $baseUrl,
            'jwks_uri' => $baseUrl . '/openidconfiguration/jwks',
            'authorization_endpoint' => $baseUrl . '/authorize',
            'token_endpoint' => $baseUrl . '/token',
            'userinfo_endpoint' => $baseUrl . '/userinfo',
            'end_session_endpoint' => $baseUrl . '/endsession',
            'check_session_iframe' => $baseUrl . '/checksession',
            'revocation_endpoint' => $baseUrl . '/revocation',
            'scopes_supported' => [],
            'claims_supported' => [
                'sub',
                'first_name',
                'last_name',
                'email',
            ],
            'grant_types_supported' => [
                'authorization_code',
                'refresh_token',
                'client_credentials',
            ],
            'response_types_supported' => [
                'token',
                'code',
            ],
            'subject_types_supported' => [
                'public'
            ],
        ];

        /** @var OAuthScope $oauthScopes */
        $oauthScopes = (new OAuthScopeModel())->find();
        foreach ($oauthScopes as $oauthScope) {
            $arr['scopes_supported'][] = $oauthScope->scope;
        }

        foreach ($arr as $key => $value) {
            Data::set($key, $value);
        }

        $response->setJSON(Data::getStore());
        $response->send();
    }

    /*
     * JSON Web Key Set [JWK] document: /openidconfiguration/jwks
     *
     * @see https://tools.ietf.org/html/rfc7517
     */
    public static function handleJwks(ResponseInterface $response): void {
        /** @var \Config\AuthExtension $authConfig */
        $authConfig = config('AuthExtension');

        // The keys in use, and the ones retired while a token they signed can still be valid. Each
        // under its own `kid` and with the algorithm stored beside it: a verifier that trusts the
        // key set rejects a token whose algorithm differs from the one named here.
        $keys = ServerLib::getInstance()->storage->getPublishedKeys($authConfig->oauthAccessTokenLifeTime ?? 900);

        $json = [
            'keys' => []
        ];
        foreach ($keys as $key) {
            // SimpleJWT presents the public key
            $data = (new RSAKey($key['public_key'], 'pem'))->getKeyData();
            $json['keys'][] = [
                'kty' => $data['kty'],
                'use' => 'sig',
                'kid' => $key['kid'],
                'e' => $data['e'],
                'n' => $data['n'],
                'alg' => $key['encryption_algorithm'],
            ];
        }

        $response->setJSON($json);
        $response->send();
    }

}
