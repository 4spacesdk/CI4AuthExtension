<?php namespace AuthExtension\Entities;

use AuthExtension\Traits\UserTrait;
use OrmExtension\Extensions\Entity;

/**
 * Class User
 * @package App\Entities
 */
class User extends Entity {

    use UserTrait;

    public function name(): string {
        return $this->first_name . (strlen($this->last_name) ? ' '.$this->last_name : '');
    }

    public function hasMFASecret(): bool {
        return strlen($this->mfa_secret_hash) > 0;
    }

    public function updateMFASecret(string $value): void {
        $cipher = 'AES-256-CBC';
        $passPhrase = hex2bin('c17319112b52d6b472136d7938121233783d723814746a67c81a451c4d238d18');
        $nonceSize = openssl_cipher_iv_length($cipher);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt(
            $value,
            $cipher,
            $passPhrase,
            OPENSSL_RAW_DATA,
            $nonce
        );

        $this->mfa_secret_hash = base64_encode($nonce . $ciphertext);
        $this->save();
    }

    public function removeMFASecret(): void {
        $this->mfa_secret_hash = null;
        $this->save();
    }

    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|User[]
     */
    public function getIterator(): \ArrayIterator {
        return parent::getIterator();
    }

}
