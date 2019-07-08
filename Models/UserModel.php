<?php namespace AuthExtension\Models;

use OrmExtension\Extensions\Model;

/**
 * Class UserModel
 * @package App\Models
 */
class UserModel extends Model {

    public $useTimestamps = true;
    public $createdField = 'created';
    public $updatedField = 'updated';

}
