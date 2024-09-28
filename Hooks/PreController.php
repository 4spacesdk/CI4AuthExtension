<?php namespace AuthExtension\Hooks;

use Config\OrmExtension;
use Config\Services;

class PreController {

    public static function execute(): void {
        if (!is_array(OrmExtension::$modelNamespace)) {
            OrmExtension::$modelNamespace = [OrmExtension::$modelNamespace];
        }
        if (!is_array(OrmExtension::$entityNamespace)) {
            OrmExtension::$entityNamespace = [OrmExtension::$entityNamespace];
        }

        OrmExtension::$modelNamespace[] = 'AuthExtension\Models\\';
        OrmExtension::$entityNamespace[] = 'AuthExtension\Entities\\';

        if (config('AuthExtension')->autoRoute) {
            $routes = Services::routes();
            $response = Services::response();

            $routes->get('openidconfiguration', function() use ($response) {
                \AuthExtension\OAuth2\OpenIdConfiguration::handle($response);
            });
            $routes->get('openidconfiguration/jwks', function() use ($response) {
                \AuthExtension\OAuth2\OpenIdConfiguration::handleJwks($response);
            });
            $routes->get('authorize', function() use ($response) {
                \AuthExtension\OAuth2\Authorize::handle($response);
            });
            $routes->get('userinfo', function() use ($response) {
                \AuthExtension\OAuth2\UserInfo::handle($response);
            });
            $routes->post('token', function() use ($response) {
                \AuthExtension\OAuth2\Token::handle($response);
            });
            $routes->post('revocation', function() use ($response) {
                \AuthExtension\OAuth2\Revocation::handle($response);
            });
            $routes->get('endsession', function() use ($response) {
                \AuthExtension\OAuth2\EndSession::handle(Services::request(), $response);
            });
            $routes->get('checksession', function() use ($response) {
                \AuthExtension\OAuth2\CheckSession::handle();
            });
            $routes->get('check', function() use ($response) {
                \AuthExtension\OAuth2\Check::handle($response);
            });
            $routes->cli('check/(:segment)', function($accessToken) use ($response) {
                if ($accessToken) {
                    $_GET['access_token'] = $accessToken;
                }
                \AuthExtension\OAuth2\Check::handle($response);
            });
        }
    }

}
