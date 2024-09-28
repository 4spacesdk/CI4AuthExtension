<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthAccessTokenModel extends Model {

    public function getTableName() {
        return 'oauth_access_tokens';
    }

}
