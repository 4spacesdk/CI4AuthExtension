<?php namespace AuthExtension\Entities;

use OrmExtension\Extensions\Entity;

/**
 * Class OAuthClient
 * @package AuthExtension
 * @property string $client_id
 * @property string $client_secret
 * @property string $redirect_uri
 * @property string $grant_types
 * @property string $scope
 * @property string $user_id
 */
class OAuthClient extends Entity {


    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthClient[]
     */
    public function getIterator(): \ArrayIterator {
        return parent::getIterator();
    }

}
