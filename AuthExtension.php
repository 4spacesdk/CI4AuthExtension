<?php namespace AuthExtension;

use AuthExtension\Config\LoginResponse;
use AuthExtension\Models\UserModel;
use AuthExtension\Entities\User;
use AuthExtension\OAuth2\ServerLib;
use OAuth2\Request;

class AuthExtension {

    public static function checkLoginWithUsernamePassword(string $username, string $password, ?string $scope = null): string {
        // Check if everything is good
        /** @var User $user */
        $user = (new UserModel())
            ->where('username', $username)
            ->find();
        if (!$user->exists()) {
            // Username not found
            return LoginResponse::UnknownUser;
        }

        if ($scope && isset($user->scope)) {
            if (count(array_diff(explode(' ', $scope), explode(' ', $user->scope))) > 0) {
                return LoginResponse::WrongScope;
            }
        }

        if (password_verify($password, $user->password)) // OK
            return $user->renew_password ? LoginResponse::RenewPassword : LoginResponse::Success;
        else { // Wrong password
            return LoginResponse::WrongPassword;
        }
    }

    public static function login(string $username, string $password, ?string $scope = null): string {
        $loginResponse = AuthExtension::checkLoginWithUsernamePassword($username, $password, $scope);

        switch ($loginResponse) {
            case LoginResponse::Success:
            case LoginResponse::RenewPassword:

                $user = (new UserModel())
                    ->where('username', $username)
                    ->find();

                self::saveUserSession($user->id);
        }

        return $loginResponse;
    }

    /**
     * Took `$password` as its second parameter before v1.3.0, and never used it. PHP does not
     * complain about an extra argument, so a call written for the old signature would have its
     * password checked as the scope; it is refused instead.
     */
    public static function checkLoginWithUsername(string $username, ?string $scope = null): string {
        if (func_num_args() > 2) {
            throw new \ArgumentCountError('checkLoginWithUsername() takes ($username, $scope) since v1.3.0; the unused $password parameter is gone');
        }

        // Check if everything is good
        /** @var User $user */
        $user = (new UserModel())
            ->where('username', $username)
            ->find();
        if (!$user->exists()) {
            // Username not found
            return LoginResponse::UnknownUser;
        }

        if ($scope && isset($user->scope)) {
            if (count(array_diff(explode(' ', $scope), explode(' ', $user->scope))) > 0) {
                return LoginResponse::WrongScope;
            }
        }

        return $user->renew_password ? LoginResponse::RenewPassword : LoginResponse::Success;
    }

    public static function loginWithUsername(string $username, ?string $scope = null): string {
        $loginResponse = AuthExtension::checkLoginWithUsername($username, $scope);

        switch ($loginResponse) {
            case LoginResponse::Success:
            case LoginResponse::RenewPassword:

                $user = (new UserModel())
                    ->where('username', $username)
                    ->find();

                self::saveUserSession($user->id);
        }

        return $loginResponse;
    }

    /**
     * @return User|bool
     */
    public static function checkSession() {
        if (session('user_id')) {
            /** @var User $user */
            $user = (new UserModel())
                ->where('id', session('user_id'))
                ->find();
            if ($user->exists()) {
                return $user;
            }
        }
        return false;
    }

    /**
     * A new session id on sign-in, and the old one deleted: an id someone planted before sign-in -
     * session fixation - would otherwise become a signed-in session they hold too.
     */
    public static function saveUserSession($id): void {
        $session = session();
        $session->regenerate(true);
        $session->set('user_id', $id);
    }

    /**
     * Deletes expired tokens and authorization codes. Meant for the application's nightly cron.
     *
     * @return array<string, int> table => rows deleted
     */
    public static function deleteExpiredTokens(): array {
        return ServerLib::getInstance()->storage->deleteExpired(self::signedTokenLifetime());
    }

    /**
     * A new signing key; the one in use is retired and stays in the key set until the tokens it
     * signed expire. See `auth:rotate-signing-key`.
     *
     * @return array{kid: string, retired: string[]} the new key's `kid`, and the retired ones'
     */
    public static function rotateSigningKey(): array {
        return ServerLib::getInstance()->storage->rotateSigningKey();
    }

    /**
     * The last step of rotating the application's encryption key: every signing key written again
     * with the current one, so the old one can be taken out of `previousKeys`. Call it from the
     * application's own re-encryption, beside its other encrypted columns.
     *
     * Needs CodeIgniter 4.7 or later, where `previousKeys` came: an older version ignores it, so
     * a key written with the old encryption key could not be read at all.
     *
     * @return array{rewritten: int, unreadable: string[]}
     */
    public static function reencryptSigningKeys(): array {
        if (version_compare(\CodeIgniter\CodeIgniter::CI_VERSION, '4.7.0', '<')) {
            throw new \RuntimeException('Rotating the encryption key needs CodeIgniter 4.7 or later, for Config\\Encryption::$previousKeys; this is ' . \CodeIgniter\CodeIgniter::CI_VERSION);
        }
        return ServerLib::getInstance()->storage->reencryptPrivateKeys();
    }

    /**
     * How long a signed token - an access token or an id token - can live, in seconds.
     */
    private static function signedTokenLifetime(): int {
        return config('AuthExtension')->oauthAccessTokenLifeTime ?? 900;
    }

    /**
     * Revokes every access token, refresh token and unused authorization code a user has - for one
     * client, or for all. For a changed or reset password: whoever held the old one is signed out.
     *
     * @return array<string, int> table => rows deleted
     */
    public static function revokeUserTokens(int|string $userId, ?string $clientId = null): array {
        return ServerLib::getInstance()->storage->deleteUserTokens((string)$userId, $clientId);
    }

    public static function authorize($scope = ''): array {
        return ServerLib::getInstance()->authorize(Request::createFromGlobals(), $scope);
    }

}
