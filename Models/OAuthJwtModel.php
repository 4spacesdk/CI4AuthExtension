<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

/**
 * Class OAuthJwtModel
 * @package AuthExtension\Models
 */
class OAuthJwtModel extends Model {

    public function getTableName() {
        return 'oauth_jwt';
    }

}
