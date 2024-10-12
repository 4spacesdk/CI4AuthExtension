<?php namespace AuthExtension;

use AuthExtension\Config\LoginResponse;
use AuthExtension\Models\UserModel;
use AuthExtension\Entities\User;
use AuthExtension\OAuth2\ServerLib;
use OAuth2\Request;

class AuthExtension {

    public static function checkLoginWithUsernamePassword(string $username, string $password, string $scope = null): string {
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

    public static function login(string $username, string $password, string $scope = null): string {
        $loginResponse = AuthExtension::checkLoginWithUsernamePassword($username, $password, $scope);

        switch ($loginResponse) {
            case LoginResponse::Success:
            case LoginResponse::RenewPassword:

                $user = (new UserModel())
                    ->where('username', $username)
                    ->find();

                $session = session();
                $session->set('user_id', $user->id);
        }

        return $loginResponse;
    }

    public static function checkLoginWithUsername(string $username, string $password, string $scope = null): string {
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

    public static function loginWithUsername(string $username, string $scope = null): string {
        $loginResponse = AuthExtension::checkLoginWithUsername($username, $scope);

        switch ($loginResponse) {
            case LoginResponse::Success:
            case LoginResponse::RenewPassword:

                $user = (new UserModel())
                    ->where('username', $username)
                    ->find();

                $session = session();
                $session->set('user_id', $user->id);
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

    public static function saveUserSession($id): void {
        $session = session();
        $session->set('user_id', $id);
    }

    public static function authorize($scope = ''): array {
        return ServerLib::getInstance()->authorize(Request::createFromGlobals(), $scope);
    }

}
