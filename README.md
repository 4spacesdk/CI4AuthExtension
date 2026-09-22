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
     * Specify the database group. The storage connects with PDO, using the group's host,
     * credentials and `encrypt` settings (TLS, see CHANGELOG v1.3.1).
     */
    public string $dbGroupName = 'default';

    /*
     * If true, AuthExtension will extend routes with default endpoints
     * Check CI4AuthExtension/Hooks/PreController.php for details
     */
    public bool $autoRoute = true;

    /*
     * OAuth Access token lifetime in seconds
     */
    public int $oauthAccessTokenLifeTime = 15 * MINUTE;

    /*
     * OAuth Access token lifetime in seconds
     */
    public int $oauthRefreshTokenLifeTime = 7 * DAY;

    /*
     * If true, the authorization endpoint requires a PKCE code challenge and
     * `oauth_authorization_codes` needs the `code_challenge` and `code_challenge_method` columns.
     * Clients that do not send a challenge are rejected with 400 missing_code_challenge.
     */
    public bool $enforcePkce = true;

    /*
     * If true, access tokens are JWTs and the whole token string is stored, so
     * `oauth_access_tokens.access_token` must be wide enough to hold it. If false, the server
     * issues opaque tokens instead.
     */
    public bool $useJwtAccessTokens = true;

    /*
     * The `iss` of id tokens and the base of the discovery document's endpoints. Empty uses
     * `base_url()`.
     */
    public string $issuer = '';

    /*
     * Path to login page
     */
    public string $loginPage = '/login';

}
```

Step 3)

Add this line to your `application/Config/Events.php` file 
```php
Events::on('pre_system', [\AuthExtension\Hooks\PreController::class, 'execute']);
Events::on('pre_command', [\AuthExtension\Hooks\PreController::class, 'execute']);
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

## Upgrading to v1.3.0

v1.3.0 keeps no credentials in the clear in the `oauth_*` tables. See CHANGELOG.md for the details.

1. Make sure the application has an encryption key (`Config\Encryption`, `encryption.key` in
   `.env`). The signing key is encrypted with it; without one it stays unencrypted, with a warning
   in the log.
2. Add a migration that calls `\AuthExtension\Migration\Upgrade_1_3_0::migrateUp()`. It hashes the
   stored tokens, authorization codes and client secrets, encrypts the signing key and indexes the
   token columns. Sessions survive it, it can run before or after the new code is deployed, and
   running it again changes nothing. It cannot be undone: take a backup first.
3. Read the tables through the storage from now on. A token is stored as
   `AuthExtension\OAuth2\Pdo::hashToken($token)`. A client secret you write yourself must be
   `Pdo::hashClientSecret($secret)` - `AuthExtension\Entities\OAuthClient` does it for you - and it
   cannot be shown again afterwards.
4. The issuer (`iss` in id tokens, and the base of the discovery document) is `base_url()` now,
   not the request's host. Set `$issuer` in `Config\AuthExtension` to keep another value.
5. `checkLoginWithUsername($username, $scope)` no longer takes the unused `$password`.
6. Sign-out follows `post_logout_redirect_uri` only when it is registered on a client, and revokes
   no tokens - see "Revoke a user's tokens".
7. Schedule `AuthExtension::deleteExpiredTokens()` - see "Delete expired tokens".
8. Run `php spark auth:rotate-signing-key` once. Until v1.3.0 the signing key was stored in the
   clear, in every dump and backup since; encrypting it does not make a copy already taken useless.
   See "Rotate the signing key".

## Authorize with session

`$user = AuthExtension::checkSession();`  
`$user` is either `FALSE` or the authorized User.

## Authorize with OAuth2

If you enable autoRoute in Config you can authorize by calling `/check` with `access_token` as query parameter or header.   
Check `AuthExtension\Hooks\PreController` for more routes.

## Delete expired tokens

Nothing removes expired tokens on its own. Call this from a nightly cron job:

```php
$deleted = AuthExtension::deleteExpiredTokens(); // ['oauth_access_tokens' => 1327, ...]
```

It deletes expired access tokens, refresh tokens, id tokens and authorization codes. A refresh
token that never expires is kept.

## Revoke a user's tokens

Sign-out (`/endsession`) ends the session but revokes no tokens: nothing ties a token to one
browser. Revoke the ones the application holds through `/revocation`. When a password is changed
or reset, sign the user out everywhere:

```php
AuthExtension::revokeUserTokens($user->id);             // every client
AuthExtension::revokeUserTokens($user->id, 'webclient'); // one client
```

`post_logout_redirect_uri` is followed only when it is a redirect uri registered on a client.

## Rotate the signing key

```
php spark auth:rotate-signing-key
```

Makes a new signing key of the same kind and size, and retires the one in use. Tokens already
signed stay valid until they expire, and the key set (`/openidconfiguration/jwks`) keeps
publishing the retired key until then, each under its own `kid`. `deleteExpiredTokens()` removes
it afterwards. Needs `Upgrade_1_3_0`.

## Rotate the encryption key

The signing key is encrypted with the application's encryption key. To rotate that key:

1. Put the new key in `Config\Encryption::$key` and the old one in `$previousKeys` - CodeIgniter
   4.7 or later; an older version ignores `previousKeys`, and the signing key can no longer be
   read.
2. Run `php spark auth:reencrypt-signing-keys`. An application with a command of its own that
   re-encrypts its data should call `AuthExtension::reencryptSigningKeys()` from that instead, so
   one command says when the old key can go. Either answers how many keys were written and which
   could not be read.
3. Re-encrypt whatever else the application encrypted with the old key.
4. Take the old key out of `previousKeys`.
