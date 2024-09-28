<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class OAuthScopeModel extends Model {

    protected $primaryKey = 'scope';

    public function getTableName() {
        return 'oauth_scopes';
    }

}
