<?php namespace AuthExtension\Migration;

use Config\Database;
use DebugTool\Data;
use OrmExtension\Migration\ColumnTypes;
use OrmExtension\Migration\Table;

class Upgrade_1_2_0 {

    public static function migrateUp() {
        Table::init('users')
            ->column('mfa_secret_hash', ColumnTypes::VARCHAR_255);
    }

    public static function migrateDown() {

    }

}
