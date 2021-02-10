<?php namespace AuthExtension\Entities;

use OrmExtension\Extensions\Entity;

/**
 * Class OAuthPublicKey
 * @package AuthExtension
 * @property string $client_id
 * @property string $public_key
 * @property string $private_key
 * @property string $encryption_algorithm
 */
class OAuthPublicKey extends Entity {


    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthPublicKey[]
     */
    public function getIterator() {
        return parent::getIterator();
    }

}
