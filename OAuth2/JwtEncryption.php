<?php namespace AuthExtension\OAuth2;

use OAuth2\Encryption\Jwt;

class JwtEncryption extends Jwt {

    /**
     * @param \Closure|null $keyId given the payload, the `kid` for the header when the payload
     *                             names none - so a verifier holding several keys picks the right one
     */
    public function __construct(private ?\Closure $keyId = null) {
    }

    protected function generateJwtHeader($payload, $algorithm): array {
        $header = parent::generateJwtHeader($payload, $algorithm);

        if (isset($payload['kid'])) {
            $header['kid'] = $payload['kid'];
        } else if ($this->keyId) {
            $header['kid'] = ($this->keyId)($payload);
        }

        return $header;
    }

}
