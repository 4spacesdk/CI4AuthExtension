<?php namespace AuthExtension\Entities;

use OrmExtension\Extensions\Entity;

/**
 * Class OAuthAuthorizationCode
 * @package AuthExtension
 * @property string $authorization_code
 * @property string $client_id
 * @property string $user_id
 * @property string $redirect_uri
 * @property string $expires
 * @property string $scope
 * @property string $id_token
 * @property string $code_challenge
 * @property string code_challenge_method
 */
class OAuthAuthorizationCode extends Entity {

    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthAuthorizationCode[]
     */
    public function getIterator(): \ArrayIterator {
        return parent::getIterator();
    }

}
