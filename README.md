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
    public $dbGroupName = 'default';
}
```

Step 3)

Add this line to your `application/Config/Events.php` file 
```php
Events::on('pre_system', [\AuthExtension\Hooks\PreController::class, 'execute']);
```