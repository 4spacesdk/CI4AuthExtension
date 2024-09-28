<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthPublicKeyModel extends Model {

    public function getTableName() {
        return 'oauth_public_keys';
    }

}
