<?php namespace AuthExtension\OAuth2;

use AuthExtension\Entities\OAuthScope;
use AuthExtension\Models\OAuthScopeModel;
use CodeIgniter\HTTP\ResponseInterface;
use DebugTool\Data;
use SimpleJWT\Keys\KeySet;
use SimpleJWT\Keys\RSAKey;

class OpenIdConfiguration {

    public static function handle(ResponseInterface $response): void {
        $baseUrl = ServerLib::getInstance()->server->getConfig('issuer');

        $arr = [
            'issuer' => $baseUrl,
            'jwks_uri' => $baseUrl . '/.well-known/openid-configuration/jwks',
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
     * JSON Web Key Set [JWK] document: /.well-known/openid-configuration/jwks
     *
     * @see https://tools.ietf.org/html/rfc7517
     */
    public static function handleJwks(ResponseInterface $response): void {
        // Fetch public key from OAuth library
        $publicKey = ServerLib::getInstance()->server->getStorage('public_key')->getPublicKey();

        // Use SimpleJWT to present the public key
        $set = new KeySet();
        $set->add(new RSAKey($publicKey, 'pem'));

        $json = [
            'keys' => []
        ];
        foreach ($set->getkeys() as $key) {
            $data = $key->getKeyData();
            $json['keys'][] = [
                'kty' => $data['kty'],
                'use' => 'sig',
                'kid' => 'id1',
                'e' => $data['e'],
                'n' => $data['n'],
                'alg' => 'RS256',
            ];
        }

        $response->setJSON($json);
        $response->send();
    }

}
