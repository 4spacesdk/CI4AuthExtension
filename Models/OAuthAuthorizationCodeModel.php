<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthAuthorizationCodeModel extends Model {

    protected $primaryKey = 'authorization_code';

    public function getTableName() {
        return 'oauth_authorization_codes';
    }

}