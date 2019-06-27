<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthRefreshTokenModel extends Model {

    protected $primaryKey = 'refresh_token';

    public function getTableName() {
        return 'oauth_refresh_tokens';
    }

}