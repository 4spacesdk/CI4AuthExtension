# CodeIgniter 4 AuthExtension

## Installation
Step 1)

`composer require 4spacesdk/ci4authextension`

Step 2)

Create new file `app/Config/AuthExtension.php` and add this content
```php
<?php namespace Config;

use CodeIgniter\Config\BaseConfig;

class AuthExtension extends BaseConfig {

    /*
     * Specify the database group
     */
    public $dbGroupName = 'default';

    /*
     * If true, AuthExtension will extend routes with default endpoints
     * Check CI4AuthExtension/Hooks/PreController.php for details
     */
    public $autoRoute   = true;

}
```

Step 3)

Add this line to your `application/Config/Events.php` file 
```php
Events::on('pre_system', [\AuthExtension\Hooks\PreController::class, 'execute']);
```


Step 4)

Add migration file and add this line to `up()`: `\AuthExtension\Migration\Setup::migrateUp();` and this line to `down()`: `\AuthExtension\Migration\Setup::migrateDown();`.

Step 5)

Seed new users, ex:
```php
$user = new User();
$user->first_name = 'Firstname';
$user->last_name = 'Lastname';
$user->username = 'some@email.com';
$user->password = password_hash('secret password', PASSWORD_BCRYPT);
$user->save();
```

Step 6) 

Add a controller and view for simple username/password login. 
You can either use your own check login algorithm or use `$loginResponse = AuthExtension::login($username, $password);` which will return one of these constants and set `user_id` in session storage.
```php
class LoginResponse {
    const Success           = 'Success';
    const RenewPassword     = 'RenewPassword';
    const WrongPassword     = 'WrongPassword';
    const UnknownUser       = 'UnknownUser';
}
```

## Authorize with session

`$user = AuthExtension::checkSession();`  
`$user` is either `FALSE` or the authorized User.

## Authorize with OAuth2

If you enable autoRoute in Config you can authorize by calling `/check` with `access_token` as query parameter or header.   
Check `AuthExtension\Hooks\PreController` for more routes.
