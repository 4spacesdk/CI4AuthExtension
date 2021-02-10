<?php namespace AuthExtension\Entities;

use AuthExtension\Traits\UserTrait;
use OrmExtension\Extensions\Entity;

/**
 * Class User
 * @package App\Entities
 */
class User extends Entity {

    use UserTrait;

    public function name(): string {
        return $this->first_name . (strlen($this->last_name) ? ' '.$this->last_name : '');
    }

    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|User[]
     */
    public function getIterator() {
        return parent::getIterator();
    }

}
