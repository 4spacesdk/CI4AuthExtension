<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

/**
 * Class OAuthRefreshTokenModel
 * @package AuthExtension\Models
 */
class OAuthRefreshTokenModel extends Model {

    protected $primaryKey = 'refresh_token';

    public function getTableName() {
        return 'oauth_refresh_tokens';
    }

}
