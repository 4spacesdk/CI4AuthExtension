<?php namespace AuthExtension\OAuth2;

use AuthExtension\AuthExtension;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Check {

    public static function handle(ResponseInterface $response): void {
        $authorized = AuthExtension::authorize(Services::request()->getGet('scope'));
        $response->setJSON($authorized);
        $response->send();
    }

}
