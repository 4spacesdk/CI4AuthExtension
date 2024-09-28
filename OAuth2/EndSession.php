<?php namespace AuthExtension\OAuth2;

use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\ResponseInterface;

class EndSession {

    public static function handle(Request $request, ResponseInterface $response): void {
        session()->destroy();
        $redirectUri = $request->getGet('post_logout_redirect_uri');
        if (!$redirectUri) {
            $redirectUri = '/';
        }
        $response->redirect($redirectUri);
    }

}
