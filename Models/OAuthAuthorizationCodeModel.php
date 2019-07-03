<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

/**
 * Class OAuthAuthorizationCodeModel
 * @package AuthExtension\Models
 */
class OAuthAuthorizationCodeModel extends Model {

    protected $primaryKey = 'authorization_code';

    public function getTableName() {
        return 'oauth_authorization_codes';
    }

}
