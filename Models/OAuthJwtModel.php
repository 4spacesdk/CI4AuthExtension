<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthJwtModel extends Model {

    public function getTableName() {
        return 'oauth_jwt';
    }

}
