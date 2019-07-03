<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

/**
 * Class OAuthClientModel
 * @package AuthExtension\Models
 */
class OAuthClientModel extends Model {

    protected $primaryKey = 'client_id';

    public function getTableName() {
        return 'oauth_clients';
    }

}
