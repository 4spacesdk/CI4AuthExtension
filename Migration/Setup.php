<?php namespace AuthExtension\Migration;

use OrmExtension\Migration\ColumnTypes;
use OrmExtension\Migration\Table;

class Setup {

    public static function migrateUp() {
        Table::init('users')
            ->create()
            ->column('user_type_id', ColumnTypes::INT)
            ->column('first_name', ColumnTypes::VARCHAR_255)
            ->column('last_name', ColumnTypes::VARCHAR_255)
            ->column('username', ColumnTypes::VARCHAR_255)
            ->column('password', ColumnTypes::VARCHAR_255)
            ->column('renew_password', ColumnTypes::BOOL_0)
            ->column('scope', ColumnTypes::VARCHAR_4095_NULL)
            ->column('mfa_secret_hash', ColumnTypes::VARCHAR_255)
            ->timestamps()
            ->softDelete();

        Table::init('oauth_clients')
            ->create('client_id', ColumnTypes::VARCHAR_127, false)
            ->column('client_secret', ColumnTypes::VARCHAR_127)
            ->column('redirect_uri', ColumnTypes::VARCHAR_2047)
            ->column('grant_types', ColumnTypes::VARCHAR_255)
            ->column('scope', ColumnTypes::VARCHAR_4095)
            ->column('user_id', ColumnTypes::VARCHAR_127);

        Table::init('oauth_access_tokens')
            ->create()
            ->column('access_token', ColumnTypes::VARCHAR_4095)
            ->column('client_id', ColumnTypes::VARCHAR_127)
            ->column('user_id', ColumnTypes::VARCHAR_127)
            ->column('expires', ColumnTypes::TIMESTAMP)
            ->column('scope', ColumnTypes::VARCHAR_4095_NULL);

        Table::init('oauth_id_tokens')
            ->create()
            ->column('id_token', ColumnTypes::VARCHAR_4095)
            ->column('client_id', ColumnTypes::VARCHAR_127)
            ->column('user_id', ColumnTypes::VARCHAR_127)
            ->column('expires', ColumnTypes::TIMESTAMP)
            ->column('nonce', ColumnTypes::VARCHAR_4095_NULL)
            ->column('claims', ColumnTypes::VARCHAR_4095_NULL);

        Table::init('oauth_authorization_codes')
            ->create()
            ->column('authorization_code', ColumnTypes::VARCHAR_4095)
            ->column('client_id', ColumnTypes::VARCHAR_127)
            ->column('user_id', ColumnTypes::VARCHAR_127)
            ->column('redirect_uri', ColumnTypes::VARCHAR_2047)
            ->column('expires', ColumnTypes::TIMESTAMP)
            ->column('scope', ColumnTypes::VARCHAR_4095_NULL)
            ->column('id_token', ColumnTypes::VARCHAR_1023_NULL)
            ->column('code_challenge', 'VARCHAR(1000)')
            ->column('code_challenge_method', 'VARCHAR(20)');

        Table::init('oauth_refresh_tokens')
            ->create()
            ->column('refresh_token', ColumnTypes::VARCHAR_4095)
            ->column('client_id', ColumnTypes::VARCHAR_127)
            ->column('user_id', ColumnTypes::VARCHAR_127)
            ->column('expires', ColumnTypes::TIMESTAMP)
            ->column('scope', ColumnTypes::VARCHAR_4095_NULL);

        Table::init('oauth_scopes')
            ->create('scope', ColumnTypes::VARCHAR_127, false)
            ->column('is_default', ColumnTypes::BOOL_0)
            ->column('description', ColumnTypes::VARCHAR_1023);

        Table::init('oauth_jwt')
            ->create()
            ->column('client_id', ColumnTypes::VARCHAR_127)
            ->column('subject', ColumnTypes::VARCHAR_127)
            ->column('public_key', ColumnTypes::VARCHAR_2047);

        Table::init('oauth_public_keys')
            ->create()
            ->column('client_id', 'VARCHAR(127)')
            ->column('public_key', ColumnTypes::VARCHAR_2047)
            ->column('private_key', ColumnTypes::VARCHAR_4095)
            ->column('encryption_algorithm', ColumnTypes::VARCHAR_127, 'RS256');
    }

    public static function migrateDown() {
        Table::init('users')->dropTable();
        Table::init('oauth_clients')->dropTable();
        Table::init('oauth_access_tokens')->dropTable();
        Table::init('oauth_id_tokens')->dropTable();
        Table::init('oauth_authorization_codes')->dropTable();
        Table::init('oauth_refresh_tokens')->dropTable();
        Table::init('oauth_scopes')->dropTable();
        Table::init('oauth_jwt')->dropTable();
        Table::init('oauth_public_keys')->dropTable();
    }

}
