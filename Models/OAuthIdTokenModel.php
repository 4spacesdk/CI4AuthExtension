<?php namespace AuthExtension\Models;

use RestExtension\Core\Model;

class OAuthIdTokenModel extends Model {

    public function getTableName() {
        return 'oauth_id_tokens';
    }

}
