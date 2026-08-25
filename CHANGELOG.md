# Changelog

## v1.2.4 (2026-08-25)

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
