<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

/**
 * Class OAuthPublicKeyModel
 * @package AuthExtension\Models
 */
class OAuthPublicKeyModel extends Model {

    public function getTableName() {
        return 'oauth_public_keys';
    }

}
