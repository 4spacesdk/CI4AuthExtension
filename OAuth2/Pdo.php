<?php namespace AuthExtension\OAuth2;

class Pdo extends \OAuth2\Storage\Pdo implements IdTokenStorageInterface {

    public function __construct($connection, $config = []) {
        parent::__construct($connection, array_merge([
            'id_token_table' => 'oauth_id_tokens',
        ], $config));
    }

    public function getIdToken($id_token) {
        $stmt = $this->db->prepare(sprintf('SELECT * from %s where id_token = :id_token', $this->config['id_token_table']));

        $token = $stmt->execute(compact('id_token'));
        if ($token = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            // convert date string back to timestamp
            $token['expires'] = strtotime($token['expires']);
        }

        return $token;
    }

    public function setIdToken($id_token, $client_id, $user_id, $expires, $nonce = null, $claims = null): bool {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        // if it exists, update it.
        if ($this->getIdToken($id_token)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET client_id = :client_id, expires = :expires, user_id = :user_id, scope = :scope, claims = :claims where id_token = :id_token', $this->config['id_token_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (id_token, client_id, expires, user_id, nonce, claims) VALUES (:id_token, :client_id, :expires, :user_id, :nonce, :claims)', $this->config['id_token_table']));
        }

        return $stmt->execute(compact('id_token', 'client_id', 'user_id', 'expires', 'nonce', 'claims'));
    }
}
