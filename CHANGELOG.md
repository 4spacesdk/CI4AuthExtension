# Changelog

## v1.3.0 (1026-09-22)

### Enhancements
* The signing key in `oauth_public_keys.private_key` is stored encrypted with the application's
  encrypter, so a database dump no longer lets anyone sign tokens. `OAuthPublicKey` encrypts it
  when set, and the storage decrypts it when signing. A key stored unencrypted is still read,
  and an application without an encryption key keeps storing it unencrypted, with a warning.
* Access tokens, refresh tokens, id tokens and authorization codes are stored as their SHA-256,
  so a database dump holds no token anyone can use. Tokens stored before this version are still
  found until `Migration\Upgrade_1_3_0` has hashed them. Read the tables through the storage:
  `AuthExtension\OAuth2\Pdo::hashToken()` gives the stored value of a token.
* Client secrets are stored with `password_hash()`. `OAuthClient` and `Pdo::setClientDetails()`
  hash a secret when it is set; an application writing `oauth_clients` otherwise should use
  `Pdo::hashClientSecret()`. A secret stored in the clear is still accepted until
  `Migration\Upgrade_1_3_0` has hashed it, and a client without a secret stays public.
* The issuer - the `iss` of id tokens, and the base of the discovery document's endpoints - is
  `base_url()`, or `Config\AuthExtension::$issuer` when set, instead of being built from the
  request's `Host` header, which the caller chooses.
* `AuthExtension::deleteExpiredTokens()` deletes expired access tokens, refresh tokens, id tokens
  and authorization codes, and answers how many went from each table. Nothing removed them
  before. Meant for the application's nightly cron; a refresh token that never expires is kept.
* `AuthExtension::revokeUserTokens($userId, $clientId = null)` revokes every access token, refresh
  token and unused authorization code a user has, for one client or all. For a changed or reset
  password.
* The signing key can be rotated: `php spark auth:rotate-signing-key` (or
  `AuthExtension::rotateSigningKey()`) makes a new one and retires the one in use, which the key
  set keeps publishing until the tokens it signed expire. Tokens name their key in `kid` - id
  tokens too, now - a thumbprint of the key, `id1` for the key from before. `oauth_public_keys`
  has `kid` and `retired` for it.
* `php spark auth:reencrypt-signing-keys` (or `AuthExtension::reencryptSigningKeys()`, for an
  application's own re-encryption command) writes the signing keys again with the current
  encryption key, the last step of rotating that key. Needs CodeIgniter 4.7 or later.

### Fixed bugs
* Upgraded bshaffer/oauth2-server-php to ^1.14.2, which declares its nullable parameters for PHP 8.4
  (20 deprecations in an application's test run).
* Sign-out followed `post_logout_redirect_uri` wherever it pointed - an open redirect from the
  application's own address. It is followed only when it is a redirect uri registered on a client:
  the client named by a verified `id_token_hint` or by `client_id`, or any client without either.
  Otherwise sign-out lands on `/`.
* Signing in kept the session id from before, so an id planted in the browser beforehand - session
  fixation - became a signed-in session. `saveUserSession()`, `login()` and `loginWithUsername()`
  give the session a new id and delete the old one.
* `loginWithUsername()` passed the scope as the password to `checkLoginWithUsername()`, so the
  scope was never checked - and without a scope it threw a `TypeError`. The scope is checked now.
* PHP 8.4 deprecations: `$scope` in the four login methods of `AuthExtension`, and `$encryptionUtil`
  in `IdTokenResponseType`, are declared nullable instead of implicitly so. `ScopeUtil` no longer
  passes a missing scope to `strlen()`.
* `oauth_authorization_codes.id_token` was `VARCHAR(1023)`. With the `openid` scope the library
  stores an id token there, and one signed with a 4096 bit key for a user with long names did not
  fit. It is `TEXT` now.
* The discovery document's `jwks_uri` named `/.well-known/openid-configuration/jwks`, which is not
  a route. It names `/openidconfiguration/jwks` now.

### Breaking changes
* Requires `codeigniter4/framework` ^4.7 and `4spacesdk/ci4ormextension` ^1.1, which it always used
  but did not declare. 4.7 is where `Config\Encryption::$previousKeys` came, which rotating the
  encryption key depends on.
* `checkLoginWithUsername($username, $scope = null)`: the unused `$password` parameter is gone. A
  call with three arguments throws an `ArgumentCountError` rather than checking the password as the
  scope.

### Upgrade guide
Step by step in README.md, "Upgrading to v1.3.0".

* Call `\AuthExtension\Migration\Upgrade_1_3_0::migrateUp()` from a migration of your own. It hashes
  the stored tokens, authorization codes and client secrets, encrypts the signing key, widens
  `oauth_public_keys.private_key` and `oauth_authorization_codes.id_token` to `TEXT`, adds `kid` and
  `retired` to `oauth_public_keys` and indexes the token columns. Sessions survive it,
  and running it again changes nothing. It cannot be undone: go back from a backup.
* The application needs an encryption key (`Config\Encryption`) for the signing key to be
  encrypted. Rotating that key: put the old one in `previousKeys`, which needs CodeIgniter 4.7 or
  later - an older version ignores it, and the signing key can no longer be read.
* `Setup` and `Upgrade_1_1_0` create `private_key` as `TEXT` now: an encrypted 4096 bit key does not
  fit in the `VARCHAR(4095)` they made before, and was cut short.
* The issuer changes for an application whose `base_url()` is not the bare host it was reached on
  - one under a path, like `https://example.com/api/`, or reached on another name. A client that
  checks `iss`, or reads the endpoints from the discovery document, sees the new value.



## v1.2.7 (2026-09-22)

### Fixed bugs
* Issuing an id token that was already stored failed with `SQLSTATE[HY093]`: the update named five
  parameters and was given six. It happens when the same id token is issued twice - the same user,
  client and nonce within the same second, as when a sign-in page is loaded twice in quick
  succession. The update sets the nonce as well now.



## v1.2.6 (2026-09-22)

### Fixed bugs
* The JSON Web Key Set named RS256 whatever the tokens were signed with. It names the algorithm
  stored in `oauth_public_keys.encryption_algorithm` now, so a verifier that trusts the key set -
  a push server, another service - accepts the tokens instead of refusing them as signed by
  another algorithm.



## v1.2.5 (2026-08-25)

### Fixed bugs


### Enhancements
* `enforce_pkce` and the JWT access token response type can now be turned off from
  `Config\AuthExtension` via `$enforcePkce` and `$useJwtAccessTokens`. Both default to true,
  so behaviour is unchanged for anyone who does not set them.

### Upgrade guide
Both settings carry a schema requirement, which is why they are now optional. `$enforcePkce`
needs `code_challenge` and `code_challenge_method` on `oauth_authorization_codes`, and rejects
clients that send no challenge. `$useJwtAccessTokens` stores the whole JWT, so
`oauth_access_tokens.access_token` has to be wide enough for it. An application upgrading from
before v1.1.0 that has not yet run `Migration\Upgrade_1_1_0` should set both to false.



## v1.2.3 (2026-08-25)

### Fixed bugs
* Upgraded kelvinmo/simplejwt to ^1.1.2, resolving CVE-2026-33204 (unauthenticated
  denial of service via JWE header tampering, affecting simplejwt <= 1.1.0). Composer
  refuses to install the previous ^0.5.3 constraint because of this advisory.

### Enhancements


### Upgrade guide
simplejwt 1.1.2 requires PHP 8.0, so the minimum PHP version is raised to 8.0.
The only usage is presenting the public key on the JWKS endpoint, and that API is
unchanged, so no calling code needs to be adjusted.



## v1.2.2 (2026-01-26)

### Fixed bugs
* Added port to dsn in OAuth2 storage config

### Enhancements


### Upgrade guide



## v1.2.1 (2024-12-02)

### Fixed bugs
* Error when trying to update id_token

### Enhancements


### Upgrade guide



## v1.2.0 (2024-10-12)

### Fixed bugs


### Enhancements
* MFA

### Upgrade guide
1. You need to run migrations
    ```
    Upgrade_1_2_0::migrateUp();
    ```


## v1.1.2 (2024-10-01)

### Fixed bugs
* `[DEPRECATED] strlen(): Passing null to parameter #1 ($string) of type string is deprecated in VENDORPATH/4spacesdk/ci4authextension/OAuth2/ScopeUtil.php on line 13`

### Enhancements


### Upgrade guide



## v1.1.1 (2024-09-30)

### Fixed bugs
* Fix migration for v1.1.0 upgrade

### Enhancements


### Upgrade guide



## v1.1.0 (2024-09-28)

### Fixed bugs

### Enhancements
* PKCE is now supported

### Breaking changes
* Implicit flow is no longer supported

### Upgrade guide
1. You need to run migrations
    ```
    Upgrade_1_1_0::migrateUp();
    ```
2. Mitigate breaking changes
