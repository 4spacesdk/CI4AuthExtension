<?php namespace AuthExtension;

use App\Models\UserModel;
use AuthExtension\Config\LoginResponse;
use AuthExtension\Entities\User;
use AuthExtension\OAuth2\ServerLib;
use DebugTool\Data;

class AuthExtension {

    public static function checkLogin(string $username, string $password): string {
        // Check if everything is good
        /** @var User $user */
        $user = (new UserModel())
            ->where('username', $username)
            ->where('password', sha1($password))
            ->find();
        if($user->exists())
            return $user->renew_password ? LoginResponse::RenewPassword : LoginResponse::Success;

        // Not ok, check if username exists
        $user = (new UserModel())
            ->where('username', $username)
            ->find();

        if($user->exists())
            return LoginResponse::WrongPassword;

        // Username not found
        return LoginResponse::UnknownUser;
    }

    public static function login(string $username, string $password): string {
        $loginResponse = AuthExtension::checkLogin($username, $password);

        switch($loginResponse) {
            case LoginResponse::Success:
            case LoginResponse::RenewPassword:

                $user = (new UserModel())
                    ->where('username', $username)
                    ->where('password', sha1($password))
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
        if(session('user_id')) {
            /** @var User $user */
            $user = (new UserModel())
                ->where('id', session('user_id'))
                ->find();
            Data::lastQuery();
            if($user->exists()) {
                return $user;
            }
        }
        return false;
    }

    public static function authorize($trySession = false) {
        $user = ServerLib::getInstance()->authorize(\OAuth2\Request::createFromGlobals());
        if($trySession && !$user)
            $user = AuthExtension::checkSession();
        return $user;
    }

}