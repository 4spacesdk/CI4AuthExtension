<?php namespace AuthExtension\Migration;

use Config\Database;
use DebugTool\Data;
use OrmExtension\Migration\ColumnTypes;
use OrmExtension\Migration\Table;

class Upgrade_1_1_0 {

    public static function migrateUp() {
        // Allow for larger private keys
        Database::connect()->query('ALTER TABLE oauth_public_keys MODIFY private_key VARCHAR(4095) NOT NULL');

        // Allow to nullable client ids in public keys
        Database::connect()->query('ALTER TABLE oauth_public_keys MODIFY client_id VARCHAR(127)');

        // Add ID Tokens
        Table::init('oauth_id_tokens')
            ->create()
            ->column('id_token', ColumnTypes::VARCHAR_4095)
            ->column('client_id', ColumnTypes::VARCHAR_127)
            ->column('user_id', ColumnTypes::VARCHAR_127)
            ->column('expires', ColumnTypes::TIMESTAMP)
            ->column('nonce', ColumnTypes::VARCHAR_4095_NULL)
            ->column('claims', ColumnTypes::VARCHAR_4095_NULL);

        // Change primary key for tokens. To allow for tokens beyond the limit of primary key length.
        Database::connect()->query('alter table oauth_access_tokens drop primary key');
        Database::connect()->query('alter table oauth_access_tokens drop column id');
        Database::connect()->query('alter table oauth_access_tokens add id int UNSIGNED not null');
        Database::connect()->query('select @i := 0');
        Database::connect()->query('update oauth_access_tokens set id = (@i := @i + 1) where id = 0');
        Database::connect()->query('alter table oauth_access_tokens add primary key (id)');
        Database::connect()->query('alter table oauth_access_tokens modify id int AUTO_INCREMENT');

        Database::connect()->query('alter table oauth_authorization_codes drop primary key');
        Database::connect()->query('alter table oauth_authorization_codes drop column id');
        Database::connect()->query('alter table oauth_authorization_codes add id int UNSIGNED not null');
        Database::connect()->query('select @i := 0');
        Database::connect()->query('update oauth_authorization_codes set id = (@i := @i + 1) where id = 0');
        Database::connect()->query('alter table oauth_authorization_codes add primary key (id)');
        Database::connect()->query('alter table oauth_authorization_codes modify id int AUTO_INCREMENT');

        Database::connect()->query('alter table oauth_refresh_tokens drop primary key');
        Database::connect()->query('alter table oauth_refresh_tokens drop column id');
        Database::connect()->query('alter table oauth_refresh_tokens add id int UNSIGNED not null');
        Database::connect()->query('select @i := 0');
        Database::connect()->query('update oauth_refresh_tokens set id = (@i := @i + 1) where id = 0');
        Database::connect()->query('alter table oauth_refresh_tokens add primary key (id)');
        Database::connect()->query('alter table oauth_refresh_tokens modify id int AUTO_INCREMENT');

        // Allow for longer tokens
        Database::connect()->query('ALTER TABLE oauth_access_tokens MODIFY access_token VARCHAR(4095) NOT NULL');
        Database::connect()->query('ALTER TABLE oauth_authorization_codes MODIFY authorization_code VARCHAR(4095) NOT NULL');
        Database::connect()->query('ALTER TABLE oauth_refresh_tokens MODIFY refresh_token VARCHAR(4095) NOT NULL');

        // Allow to nullable refresh token scope
        Database::connect()->query('ALTER TABLE oauth_refresh_tokens MODIFY scope VARCHAR(4095)');
    }

    public static function migrateDown() {

    }

}
