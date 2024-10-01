# Changelog

## v1.1.2 (2024-01-01)

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
