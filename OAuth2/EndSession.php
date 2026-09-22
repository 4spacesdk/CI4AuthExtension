<?php namespace AuthExtension\OAuth2;

use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Ends the session, and sends the browser on to `post_logout_redirect_uri` - only when that is a
 * redirect uri registered on a client. It was followed wherever it pointed, so a link to sign-out
 * could send someone anywhere, from the application's own address.
 *
 * The client is the audience of `id_token_hint`, once its signature is verified, or `client_id`.
 * Without either, the uri may be registered on any client.
 *
 * Tokens are not revoked here: nothing ties a token to this browser, and revoking every token the
 * user has would sign them out everywhere. The application revokes the ones it holds - through
 * the revocation endpoint - and `AuthExtension::revokeUserTokens()` is there for a changed password.
 */
class EndSession {

    public static function handle(Request $request, ResponseInterface $response): void {
        session()->destroy();

        $redirectUri = (string)$request->getGet('post_logout_redirect_uri');
        $clientId = self::clientFromIdTokenHint((string)$request->getGet('id_token_hint'))
            ?? ($request->getGet('client_id') ?: null);

        if ($redirectUri === '' || !self::isRegistered($redirectUri, $clientId)) {
            $redirectUri = '/';
        }
        $response->redirect($redirectUri);
    }

    private static function clientFromIdTokenHint(string $idToken): ?string {
        if ($idToken === '') {
            return null;
        }

        $storage = ServerLib::getInstance()->storage;
        $algorithm = $storage->getEncryptionAlgorithm() ?: 'RS256';
        $payload = (new JwtEncryption())->decode($idToken, $storage->getPublicKey(), [$algorithm]);

        return is_array($payload) && isset($payload['aud']) && is_string($payload['aud']) ? $payload['aud'] : null;
    }

    private static function isRegistered(string $redirectUri, ?string $clientId): bool {
        $storage = ServerLib::getInstance()->storage;

        foreach ($storage->getRedirectUris($clientId) as $registered) {
            if (hash_equals($registered, $redirectUri)) {
                return true;
            }
        }
        return false;
    }

}
