<?php namespace AuthExtension\Entities;

use AuthExtension\OAuth2\Pdo;
use OrmExtension\Extensions\Entity;

/**
 * Class OAuthClient
 * @package AuthExtension
 * @property string $client_id
 * @property string $client_secret stored hashed, see Pdo::hashClientSecret()
 * @property string $redirect_uri
 * @property string $grant_types
 * @property string $scope
 * @property string $user_id
 */
class OAuthClient extends Entity {

    public function __set(string $key, $value = null) {
        if ($key === 'client_secret' && is_string($value)) {
            $value = Pdo::hashClientSecret($value);
        }

        return parent::__set($key, $value);
    }

    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthClient[]
     */
    public function getIterator(): \ArrayIterator {
        return parent::getIterator();
    }

}
