# Changelog

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
