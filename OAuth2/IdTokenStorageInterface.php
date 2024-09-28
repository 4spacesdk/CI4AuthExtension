<?php namespace AuthExtension\OAuth2;

interface IdTokenStorageInterface {

    public function getIdToken($id_token);

    public function setIdToken($id_token, $client_id, $user_id, $expires, $nonce = null, $claims = null);

}
