<?php namespace AuthExtension\Migration;

use AuthExtension\OAuth2\KeyEncryption;
use AuthExtension\OAuth2\Pdo;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use OrmExtension\Migration\Table;

/**
 * No credentials in the clear in the oauth tables: tokens and authorization codes become their
 * SHA-256, the signing key is encrypted, and client secrets are hashed. See `OAuth2\Pdo` and
 * `OAuth2\KeyEncryption`. And the signing key can be rotated: `kid` and `retired` on
 * `oauth_public_keys`, see `Pdo::rotateSigningKey()`.
 *
 * Running it again changes nothing: a token already hashed, a key already encrypted and a
 * secret already hashed are left as they are. Existing sessions survive, as their tokens are
 * hashed rather than removed.
 *
 * The token columns stay `VARCHAR(4095)` and get an index on their first 64 characters, rather
 * than being narrowed to the hash: code from before v1.3.0 still writing whole tokens - a pod
 * not yet replaced during a rolling upgrade - keeps working.
 */
class Upgrade_1_3_0 {

    public const TOKEN_COLUMNS = [
        'oauth_access_tokens' => 'access_token',
        'oauth_refresh_tokens' => 'refresh_token',
        'oauth_id_tokens' => 'id_token',
        'oauth_authorization_codes' => 'authorization_code',
    ];

    public static function migrateUp() {
        $db = Database::connect();

        foreach (self::TOKEN_COLUMNS as $table => $column) {
            $db->query("UPDATE `$table` SET `$column` = SHA2(`$column`, 256) WHERE `$column` NOT REGEXP '^[0-9a-f]{64}$'");
        }
        self::addTokenIndexes($db);

        // With the `openid` scope the library puts an id token in the authorization code, and hands
        // it back from there. Signed with a 4096 bit key it came within a few characters of 1023,
        // and a `kid` longer than `id1` - any key after the first rotation - takes it over.
        $db->query('ALTER TABLE oauth_authorization_codes MODIFY id_token TEXT NULL');

        // Encrypted, a 4096 bit key no longer fits in 4095 characters.
        $db->query('ALTER TABLE oauth_public_keys MODIFY private_key TEXT NOT NULL');
        self::addKeyRotationColumns();
        // The key in use keeps the `kid` every token has carried so far.
        $db->query("UPDATE oauth_public_keys SET kid = 'id1' WHERE kid IS NULL");
        foreach ($db->table('oauth_public_keys')->select('id, private_key')->get()->getResultArray() as $row) {
            if (!KeyEncryption::isEncrypted($row['private_key'])) {
                $db->table('oauth_public_keys')
                    ->where('id', $row['id'])
                    ->update(['private_key' => KeyEncryption::encrypt($row['private_key'])]);
            }
        }

        foreach ($db->table('oauth_clients')->select('client_id, client_secret')->get()->getResultArray() as $row) {
            $hashed = Pdo::hashClientSecret($row['client_secret']);
            if ($hashed !== $row['client_secret']) {
                $db->table('oauth_clients')
                    ->where('client_id', $row['client_id'])
                    ->update(['client_secret' => $hashed]);
            }
        }
    }

    /**
     * `kid` names a key in the key set and in the header of the tokens it signs. A key with
     * `retired` set is no longer used to sign, and is published until the tokens it signed expire.
     */
    public static function addKeyRotationColumns(): void {
        Table::init('oauth_public_keys')
            ->column('kid', 'VARCHAR(64) NULL')
            ->column('retired', 'DATETIME NULL');
    }

    /**
     * Tokens are looked up by their hash, which is 64 characters.
     */
    public static function addTokenIndexes(?BaseConnection $db = null): void {
        $db ??= Database::connect();
        foreach (self::TOKEN_COLUMNS as $table => $column) {
            $index = "{$table}_{$column}";
            if ($db->query("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index])->getNumRows() === 0) {
                $db->query("CREATE INDEX `$index` ON `$table` (`$column`(64))");
            }
        }
    }

    /**
     * Hashes cannot be turned back into tokens, nor secrets. The key is left encrypted, which
     * v1.2 cannot read: restore it from a backup to go back.
     */
    public static function migrateDown() {

    }

}
