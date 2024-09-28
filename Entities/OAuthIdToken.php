<?php namespace AuthExtension\Entities;

use OrmExtension\Extensions\Entity;

/**
 * Class OAuthIdToken
 * @property string $id_token
 * @property string $client_id
 * @property string $user_id
 * @property string $expires
 * @property string $nonce
 * @property string $claims
 */
class OAuthIdToken extends Entity {

    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthIdToken[]
     */
    public function getIterator(): \ArrayIterator {
        return parent::getIterator();
    }

}
