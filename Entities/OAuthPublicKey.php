<?php namespace AuthExtension\Entities;

use AuthExtension\OAuth2\KeyEncryption;
use OrmExtension\Extensions\Entity;

/**
 * Class OAuthPublicKey
 * @package AuthExtension
 * @property string $client_id
 * @property string $public_key
 * @property string $private_key stored encrypted, read in the clear, see KeyEncryption
 * @property string $encryption_algorithm
 * @property string|null $kid the key's name in the key set and in token headers; null is `id1`
 * @property string|null $retired when it stopped signing; null while in use
 */
class OAuthPublicKey extends Entity {

    public function __get(string $key) {
        $value = parent::__get($key);

        return $key === 'private_key' && is_string($value) ? KeyEncryption::decrypt($value) : $value;
    }

    public function __set(string $key, $value = null) {
        if ($key === 'private_key' && is_string($value)) {
            $value = KeyEncryption::encrypt($value);
        }

        return parent::__set($key, $value);
    }

    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthPublicKey[]
     */
    public function getIterator(): \ArrayIterator {
        return parent::getIterator();
    }

}
