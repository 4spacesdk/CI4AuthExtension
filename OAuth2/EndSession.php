<?php namespace AuthExtension\OAuth2;

use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;

/**
 * Class EndSession
 * @package AuthExtension\OAuth2
 */
class EndSession {

    /**
     * @param Request $request
     * @param Response $response
     */
    public static function handle($request, $response) {
        session()->destroy();
        $redirectUri = $request->getGet('post_logout_redirect_uri');
        if(!$redirectUri)
            $redirectUri = '/';
        $response->redirect($redirectUri);
    }

}