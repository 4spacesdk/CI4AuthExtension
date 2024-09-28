<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthAuthorizationCodeModel extends Model {

    public function getTableName() {
        return 'oauth_authorization_codes';
    }

}
