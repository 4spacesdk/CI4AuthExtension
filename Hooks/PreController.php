<?php namespace AuthExtension\Hooks;

use Config\OrmExtension;

class PreController {

    public static function execute() {
        if(!is_array(OrmExtension::$modelNamespace))
            OrmExtension::$modelNamespace = [OrmExtension::$modelNamespace];
        if(!is_array(OrmExtension::$entityNamespace))
            OrmExtension::$entityNamespace = [OrmExtension::$entityNamespace];

        OrmExtension::$modelNamespace[] = 'AuthExtension\Models\\';
        OrmExtension::$entityNamespace[] = 'AuthExtension\Entities\\';
    }

}