<?php namespace AuthExtension\OAuth2;

use OAuth2\Encryption\Jwt;

class JwtEncryption extends Jwt {

    protected function generateJwtHeader($payload, $algorithm): array {
        $header = parent::generateJwtHeader($payload, $algorithm);

        if (isset($payload['kid'])) {
            $header['kid'] = $payload['kid'];
        }

        return $header;
    }

}
