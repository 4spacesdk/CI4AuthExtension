<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

class UserModel extends Model {

    public $useTimestamps = true;
    public $createdField = 'created';
    public $updatedField = 'updated';

}
