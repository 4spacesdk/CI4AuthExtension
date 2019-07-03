<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

/**
 * Class OAuthScopeModel
 * @package AuthExtension\Models
 */
class OAuthScopeModel extends Model {

    protected $primaryKey = 'scope';

    public function getTableName() {
        return 'oauth_scopes';
    }

}
