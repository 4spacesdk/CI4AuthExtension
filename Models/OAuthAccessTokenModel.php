<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthAccessTokenModel extends Model {

    protected $primaryKey = 'access_token';

    public function getTableName() {
        return 'oauth_access_tokens';
    }

}