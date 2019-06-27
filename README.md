# CodeIgniter 4 AuthExtension

## Installation
Step 1)

`composer require 4spacesdk/ci4authextension`

Step 2)

Add this line to your `application/Config/Events.php` file 
```php
Events::on('pre_system', [\AuthExtension\Hooks\PreController::class, 'execute']);
```