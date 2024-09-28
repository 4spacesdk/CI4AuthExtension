<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthRefreshTokenModel extends Model {

    public function getTableName() {
        return 'oauth_refresh_tokens';
    }

}
