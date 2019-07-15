<?php namespace AuthExtension\Hooks;

use CodeIgniter\Config\Config;
use Config\OrmExtension;
use Config\Services;

class PreController {

    public static function execute() {
        if(!is_array(OrmExtension::$modelNamespace))
            OrmExtension::$modelNamespace = [OrmExtension::$modelNamespace];
        if(!is_array(OrmExtension::$entityNamespace))
            OrmExtension::$entityNamespace = [OrmExtension::$entityNamespace];

        OrmExtension::$modelNamespace[] = 'AuthExtension\Models\\';
        OrmExtension::$entityNamespace[] = 'AuthExtension\Entities\\';

        if(Config::get('AuthExtension')->autoRoute) {
            $routes = Services::routes(true);
            $response = Services::response();

            $routes->get('openidconfiguration', function() use ($response) {
                \AuthExtension\OAuth2\OpenIdConfiguration::handle($response);
                exit(0);
            });
            $routes->get('authorize', function() use ($response) {
                \AuthExtension\OAuth2\Authorize::handle($response);
                exit(0);
            });
            $routes->get('userinfo', function() use ($response) {
                \AuthExtension\OAuth2\UserInfo::handle($response);
                exit(0);
            });
            $routes->post('token', function() use ($response) {
                \AuthExtension\OAuth2\Token::handle($response);
                exit(0);
            });
            $routes->post('revocation', function() use ($response) {
                \AuthExtension\OAuth2\Revocation::handle($response);
                exit(0);
            });
            $routes->get('endsession', function() use ($response) {
                \AuthExtension\OAuth2\EndSession::handle(Services::request(), $response);
                exit(0);
            });
            $routes->get('checksession', function() use ($response) {
                \AuthExtension\OAuth2\CheckSession::handle();
                exit(0);
            });
            $routes->get('check', function() use ($response) {
                \AuthExtension\OAuth2\Check::handle($response);
                exit(0);
            });
            $routes->cli('check/(:segment)', function($accessToken) use ($response) {
                if($accessToken) $_GET['access_token'] = $accessToken;
                \AuthExtension\OAuth2\Check::handle($response);
                exit(0);
            });
        }
    }

}
