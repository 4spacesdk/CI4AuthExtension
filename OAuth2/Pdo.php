<?php namespace AuthExtension\OAuth2;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use SimpleJWT\Keys\RSAKey;

/**
 * Tokens are stored as their SHA-256, not as they were issued, so a database dump holds no
 * token anyone can use. Every lookup hashes the token it is given, and a row handed back
 * carries the token as given - the library uses it to expire or unset the row afterwards.
 *
 * Rows stored before v1.3.0 hold the token itself, until `Migration\Upgrade_1_3_0` has hashed
 * them. They are still found, so an installation running this code before the migration keeps
 * its sessions. A token shaped like a hash is never looked up as it is: a hash is what a dump
 * holds, and the library's own tokens are 40 hex characters or JWTs.
 */
class Pdo extends \OAuth2\Storage\Pdo implements IdTokenStorageInterface {

    public function __construct($connection, $config = []) {
        parent::__construct($connection, array_merge([
            'id_token_table' => 'oauth_id_tokens',
        ], $config));
    }

    public static function hashToken(string $token): string {
        return hash('sha256', $token);
    }

    /**
     * A client secret as it is stored. `password_hash()` rather than SHA-256: unlike a token, a
     * secret can be chosen by a person, and it is looked up by `client_id`, so it does not have
     * to be indexable. Empty stays empty - that is what makes a client public - and a secret
     * already hashed is handed back as it is.
     */
    public static function hashClientSecret(?string $secret): ?string {
        if ($secret === null || $secret === '' || self::isHashedClientSecret($secret)) {
            return $secret;
        }
        return password_hash($secret, PASSWORD_DEFAULT);
    }

    public static function isHashedClientSecret(?string $secret): bool {
        return is_string($secret) && password_get_info($secret)['algoName'] !== 'unknown';
    }

    /**
     * A secret stored before v1.3.0 is in the clear until `Migration\Upgrade_1_3_0` has hashed
     * it, and is compared as it is. A stored hash is never compared as it is, so the hash from a
     * dump is not a secret.
     */
    public function checkClientCredentials($client_id, $client_secret = null) {
        $client = $this->getClientDetails($client_id);
        if (!$client) {
            return false;
        }

        $stored = (string)($client['client_secret'] ?? '');
        $given = (string)$client_secret;
        if ($stored === '') {
            return $given === '';
        }
        if (self::isHashedClientSecret($stored)) {
            return password_verify($given, $stored);
        }
        return hash_equals($stored, $given);
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null) {
        return parent::setClientDetails($client_id, self::hashClientSecret($client_secret), $redirect_uri, $grant_types, $scope, $user_id);
    }

    /**
     * The key in use is the newest row not retired, preferring one for the client. Before
     * `Migration\Upgrade_1_3_0` has added the rotation columns, the library's own choice.
     */
    public function getPrivateKey($client_id = null) {
        $key = $this->activeKey($client_id);
        return KeyEncryption::decrypt($key ? $key['private_key'] : parent::getPrivateKey($client_id));
    }

    public function getPublicKey($client_id = null) {
        $key = $this->activeKey($client_id);
        return $key ? $key['public_key'] : parent::getPublicKey($client_id);
    }

    public function getEncryptionAlgorithm($client_id = null) {
        $key = $this->activeKey($client_id);
        return $key ? ($key['encryption_algorithm'] ?: 'RS256') : parent::getEncryptionAlgorithm($client_id);
    }

    /**
     * The `kid` tokens are signed under. A key from before v1.3.0 is `id1`, which is what every
     * token carried then.
     */
    public function getKeyId($client_id = null): string {
        $key = $this->activeKey($client_id);
        return $key && $key['kid'] ? $key['kid'] : 'id1';
    }

    /**
     * The keys a verifier needs: the ones in use, and the ones retired less than `$retiredFor`
     * seconds ago - tokens signed with them are still valid.
     *
     * @return array<int, array{kid: string, public_key: string, encryption_algorithm: string}>
     */
    public function getPublishedKeys(int $retiredFor): array {
        if (!$this->hasRotationColumns()) {
            return [['kid' => 'id1', 'public_key' => $this->getPublicKey(), 'encryption_algorithm' => $this->getEncryptionAlgorithm()]];
        }

        $stmt = $this->db->prepare(sprintf('SELECT kid, public_key, encryption_algorithm FROM %s WHERE retired IS NULL OR retired > :since ORDER BY retired IS NULL DESC, id DESC', $this->config['public_key_table']));
        $stmt->execute(['since' => date('Y-m-d H:i:s', time() - $retiredFor)]);

        return array_map(fn(array $key) => [
            'kid' => $key['kid'] ?: 'id1',
            'public_key' => $key['public_key'],
            'encryption_algorithm' => $key['encryption_algorithm'] ?: 'RS256',
        ], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * A new signing key, of the kind and size of the one in use, which is retired. Tokens signed
     * with the old one stay valid until they expire: they are looked up in the token table, and
     * the key set keeps publishing the old key for as long as a token signed with it can live.
     * Only the key shared by all clients is rotated.
     *
     * @return array{kid: string, retired: string[]}
     */
    public function rotateSigningKey(): array {
        if (!$this->hasRotationColumns()) {
            throw new \RuntimeException('oauth_public_keys has no rotation columns yet: run Migration\\Upgrade_1_3_0 first');
        }

        $table = $this->config['public_key_table'];
        $current = $this->activeKey(null);
        $algorithm = $current ? ($current['encryption_algorithm'] ?: 'RS256') : 'RS256';
        $bits = $current ? (openssl_pkey_get_details(openssl_pkey_get_public($current['public_key']))['bits'] ?? 4096) : 4096;

        $key = openssl_pkey_new(['private_key_bits' => $bits, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        $publicKey = openssl_pkey_get_details($key)['key'];
        $kid = (new RSAKey($publicKey, 'pem'))->getThumbnail();

        $retired = $this->db->prepare(sprintf('SELECT kid FROM %s WHERE client_id IS NULL AND retired IS NULL', $table));
        $retired->execute();
        $retiredKids = array_map(fn($kid) => $kid ?: 'id1', $retired->fetchAll(\PDO::FETCH_COLUMN));

        $this->db->beginTransaction();
        try {
            $this->db->prepare(sprintf('UPDATE %s SET retired = :now WHERE client_id IS NULL AND retired IS NULL', $table))
                ->execute(['now' => date('Y-m-d H:i:s')]);
            $this->db->prepare(sprintf('INSERT INTO %s (client_id, public_key, private_key, encryption_algorithm, kid) VALUES (NULL, :public_key, :private_key, :algorithm, :kid)', $table))
                ->execute([
                    'public_key' => $publicKey,
                    'private_key' => KeyEncryption::encrypt($privateKey),
                    'algorithm' => $algorithm,
                    'kid' => $kid,
                ]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['kid' => $kid, 'retired' => $retiredKids];
    }

    /**
     * Every stored private key written again with the current encryption key, so a rotated one
     * can be taken out of `previousKeys`. A key stored in the clear is encrypted. One no key can
     * read is left as it is and reported.
     *
     * @return array{rewritten: int, unreadable: string[]} `oauth_public_keys#id` of each unreadable
     */
    public function reencryptPrivateKeys(): array {
        $table = $this->config['public_key_table'];
        $result = ['rewritten' => 0, 'unreadable' => []];
        $update = $this->db->prepare(sprintf('UPDATE %s SET private_key = :private_key WHERE id = :id', $table));

        foreach ($this->db->query(sprintf('SELECT id, private_key FROM %s', $table))->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if ((string)$row['private_key'] === '') {
                continue;
            }
            try {
                $update->execute(['private_key' => KeyEncryption::encrypt(KeyEncryption::decrypt($row['private_key'])), 'id' => $row['id']]);
                $result['rewritten']++;
            } catch (EncryptionException) {
                $result['unreadable'][] = "{$table}#{$row['id']}";
            }
        }

        return $result;
    }

    /**
     * @return array|false the key row in use - newest not retired, the client's own first
     */
    private function activeKey($client_id) {
        if (!$this->hasRotationColumns()) {
            return false;
        }

        $stmt = $this->db->prepare(sprintf('SELECT * FROM %s WHERE (client_id = :client_id OR client_id IS NULL) AND retired IS NULL ORDER BY client_id IS NOT NULL DESC, id DESC LIMIT 1', $this->config['public_key_table']));
        $stmt->execute(['client_id' => $client_id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private bool $rotationColumnsFound = false;

    /**
     * `kid` and `retired` come with `Migration\Upgrade_1_3_0`; code running before it keeps to the
     * one key. Only a yes is remembered, so a migration in the same process is noticed.
     */
    private function hasRotationColumns(): bool {
        if (!$this->rotationColumnsFound) {
            try {
                $this->db->query(sprintf('SELECT kid, retired FROM %s LIMIT 0', $this->config['public_key_table']));
                $this->rotationColumnsFound = true;
            } catch (\PDOException) {
            }
        }
        return $this->rotationColumnsFound;
    }

    public function getAccessToken($access_token) {
        return $this->findToken($this->config['access_token_table'], 'access_token', $access_token);
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {
        return $this->storeToken($this->config['access_token_table'], 'access_token', $access_token, [
            'client_id' => $client_id,
            'user_id' => $user_id,
            'expires' => date('Y-m-d H:i:s', $expires),
            'scope' => $scope,
        ]);
    }

    public function unsetAccessToken($access_token) {
        return $this->deleteToken($this->config['access_token_table'], 'access_token', $access_token);
    }

    public function getAuthorizationCode($code) {
        return $this->findToken($this->config['code_table'], 'authorization_code', $code);
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null, $code_challenge = null, $code_challenge_method = null) {
        return $this->storeToken($this->config['code_table'], 'authorization_code', $code, [
            'client_id' => $client_id,
            'user_id' => $user_id,
            'redirect_uri' => $redirect_uri,
            'expires' => date('Y-m-d H:i:s', $expires),
            'scope' => $scope,
            'id_token' => $id_token,
            'code_challenge' => $code_challenge,
            'code_challenge_method' => $code_challenge_method,
        ]);
    }

    public function expireAuthorizationCode($code) {
        $this->deleteToken($this->config['code_table'], 'authorization_code', $code);
        return true;
    }

    public function getRefreshToken($refresh_token) {
        return $this->findToken($this->config['refresh_token_table'], 'refresh_token', $refresh_token);
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null) {
        return $this->storeToken($this->config['refresh_token_table'], 'refresh_token', $refresh_token, [
            'client_id' => $client_id,
            'user_id' => $user_id,
            'expires' => date('Y-m-d H:i:s', $expires),
            'scope' => $scope,
        ]);
    }

    public function unsetRefreshToken($refresh_token) {
        return $this->deleteToken($this->config['refresh_token_table'], 'refresh_token', $refresh_token);
    }

    public function getIdToken($id_token) {
        return $this->findToken($this->config['id_token_table'], 'id_token', $id_token);
    }

    public function setIdToken($id_token, $client_id, $user_id, $expires, $nonce = null, $claims = null): bool {
        return $this->storeToken($this->config['id_token_table'], 'id_token', $id_token, [
            'client_id' => $client_id,
            'user_id' => $user_id,
            'expires' => date('Y-m-d H:i:s', $expires),
            'nonce' => $nonce,
            'claims' => $claims,
        ]);
    }

    /**
     * The redirect uris registered on a client, or on every client. The library stores several
     * separated by spaces.
     *
     * @return string[]
     */
    public function getRedirectUris(?string $clientId = null): array {
        if ($clientId !== null) {
            $stmt = $this->db->prepare(sprintf('SELECT redirect_uri FROM %s WHERE client_id = :client_id', $this->config['client_table']));
            $stmt->execute(['client_id' => $clientId]);
        } else {
            $stmt = $this->db->query(sprintf('SELECT redirect_uri FROM %s', $this->config['client_table']));
        }

        $uris = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $registered) {
            array_push($uris, ...preg_split('/\s+/', (string)$registered, -1, PREG_SPLIT_NO_EMPTY));
        }
        return $uris;
    }

    /**
     * Deletes a user's access tokens, refresh tokens and unused authorization codes - for one
     * client, or for all of them. Signs the user out everywhere the tokens were used.
     *
     * @return array<string, int> table => rows deleted
     */
    public function deleteUserTokens(string $userId, ?string $clientId = null): array {
        $deleted = [];
        foreach (['access_token_table', 'refresh_token_table', 'code_table'] as $table) {
            $sql = sprintf('DELETE FROM %s WHERE user_id = :user_id', $this->config[$table]);
            $params = ['user_id' => $userId];
            if ($clientId !== null) {
                $sql .= ' AND client_id = :client_id';
                $params['client_id'] = $clientId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $deleted[$this->config[$table]] = $stmt->rowCount();
        }
        return $deleted;
    }

    /**
     * Deletes the tokens and authorization codes that have expired. Nothing else removes them, so
     * the tables otherwise grow by every sign-in and every refresh.
     *
     * A row that never expires is kept: the library stores that as the epoch - `date()` of 0 -
     * which is why only rows expired after 1971 are counted as expired. The cutoff is PHP's
     * clock, as `expires` was written with it.
     *
     * @param int|null $retiredKeysAfter seconds after which a retired signing key goes too
     * @return array<string, int> table => rows deleted
     */
    public function deleteExpired(?int $retiredKeysAfter = null): array {
        $now = date('Y-m-d H:i:s');
        $deleted = [];
        foreach (['access_token_table', 'refresh_token_table', 'id_token_table', 'code_table'] as $table) {
            $stmt = $this->db->prepare(sprintf("DELETE FROM %s WHERE expires < :now AND expires > '1971-01-01'", $this->config[$table]));
            $stmt->execute(['now' => $now]);
            $deleted[$this->config[$table]] = $stmt->rowCount();
        }

        // A retired signing key is kept while a token signed with it can still be valid.
        if ($retiredKeysAfter !== null && $this->hasRotationColumns()) {
            $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE retired IS NOT NULL AND retired < :cutoff', $this->config['public_key_table']));
            $stmt->execute(['cutoff' => date('Y-m-d H:i:s', time() - $retiredKeysAfter)]);
            $deleted[$this->config['public_key_table']] = $stmt->rowCount();
        }

        return $deleted;
    }

    /**
     * The values a token is looked up by: its hash, and the token itself for a row stored before
     * v1.3.0 - unless it is shaped like a hash.
     *
     * @return string[]
     */
    private static function storedForms(string $token): array {
        $forms = [self::hashToken($token)];
        if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
            $forms[] = $token;
        }
        return $forms;
    }

    /**
     * @return array|false the row, with `expires` as a timestamp and the token as given
     */
    private function findToken(string $table, string $column, $token) {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $stmt = $this->db->prepare(sprintf('SELECT * FROM %s WHERE %s = :token', $table, $column));
        foreach (self::storedForms($token) as $form) {
            $stmt->execute(['token' => $form]);
            if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $row[$column] = $token;
                $row['expires'] = strtotime($row['expires']);
                return $row;
            }
        }

        return false;
    }

    private function storeToken(string $table, string $column, string $token, array $fields): bool {
        $values = $fields + ['token' => self::hashToken($token)];

        $exists = $this->db->prepare(sprintf('SELECT 1 FROM %s WHERE %s = :token', $table, $column));
        $exists->execute(['token' => $values['token']]);

        if ($exists->fetchColumn()) {
            $set = implode(', ', array_map(fn($field) => "$field = :$field", array_keys($fields)));
            $sql = sprintf('UPDATE %s SET %s WHERE %s = :token', $table, $set, $column);
        } else {
            $sql = sprintf('INSERT INTO %s (%s, %s) VALUES (:token, :%s)',
                $table, $column, implode(', ', array_keys($fields)), implode(', :', array_keys($fields)));
        }

        return $this->db->prepare($sql)->execute($values);
    }

    private function deleteToken(string $table, string $column, $token): bool {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $deleted = 0;
        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE %s = :token', $table, $column));
        foreach (self::storedForms($token) as $form) {
            $stmt->execute(['token' => $form]);
            $deleted += $stmt->rowCount();
        }

        return $deleted > 0;
    }
}
