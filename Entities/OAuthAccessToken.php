<?php namespace AuthExtension\Entities;

use OrmExtension\Extensions\Entity;

/**
 * Class OAuthAccessToken
 * @package AuthExtension
 * @property string $access_token
 * @property string $client_id
 * @property string $user_id
 * @property string $expires
 * @property string $scope
 */
class OAuthAccessToken extends Entity {


    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthAccessToken[]
     */
    public function getIterator() {
        return parent::getIterator();
    }

}
