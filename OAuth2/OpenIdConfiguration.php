<?php namespace AuthExtension\OAuth2;

use AuthExtension\Entities\OAuthScope;
use AuthExtension\Models\OAuthScopeModel;
use CodeIgniter\HTTP\Response;
use DebugTool\Data;

/**
 * Class OpenIdConfiguration
 * @package App\Controllers
 */
class OpenIdConfiguration {

    /**
     * @param Response $response
     */
    public static function handle($response) {
        // Gets the base URL.
        $baseUrl = base_url();

        $arr = [
            'issuer'                                => $baseUrl,
            'authorization_endpoint'                => $baseUrl . '/authorize',
            'token_endpoint'                        => $baseUrl . '/token',
            'userinfo_endpoint'                     => $baseUrl . '/userinfo',
            'end_session_endpoint'                  => $baseUrl . '/endsession',
            'check_session_iframe'                  => $baseUrl . '/checksession',
            'revocation_endpoint'                   => $baseUrl . '/revocation',
            'scopes_supported'                      => [],
            'claims_supported'                      => ['sub'],
            'grant_types_supported'                 => ['implicit', 'client_credentials'],
            'response_types_supported'              => ['token'],
            'subject_types_supported'               => ['public']
        ];

        /** @var OAuthScope $oauthScopes */
        $oauthScopes = (new OAuthScopeModel())->find();
        foreach($oauthScopes as $oauthScope)
            $arr['scopes_supported'][] = $oauthScope->scope;

        foreach($arr as $key => $value)
            Data::set($key, $value);

        $response->setJSON(Data::getStore());
        $response->send();
    }

}
