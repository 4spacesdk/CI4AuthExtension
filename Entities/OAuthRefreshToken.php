<?php namespace AuthExtension\Entities;

use OrmExtension\Extensions\Entity;

/**
 * Class OAuthRefreshToken
 * @package AuthExtension
 * @property string $refresh_token
 * @property string $client_id
 * @property string $user_id
 * @property string $expires
 * @property string $scope
 */
class OAuthRefreshToken extends Entity {


    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthRefreshToken[]
     */
    public function getIterator() {
        return parent::getIterator();
    }

}
