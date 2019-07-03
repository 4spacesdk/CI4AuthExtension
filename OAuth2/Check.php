<?php namespace AuthExtension\OAuth2;

use AuthExtension\AuthExtension;
use CodeIgniter\HTTP\Response;
use Config\Services;

class Check {

    /**
     * @param Response $response
     */
    public static function handle(Response $response) {
        $authorized = AuthExtension::authorize(Services::request()->getGet('scope'));
        $response->setJSON($authorized);
        $response->send();
    }

}
