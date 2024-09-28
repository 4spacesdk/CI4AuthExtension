<?php namespace AuthExtension\Entities;

use OrmExtension\Extensions\Entity;

/**
 * Class OAuthScope
 * @package AuthExtension
 * @property string $scope
 * @property bool $is_default
 * @property string $description
 */
class OAuthScope extends Entity {

    /**
     * @return \ArrayIterator|Entity[]|\Traversable|OAuthScope[]
     */
    public function getIterator(): \ArrayIterator {
        return parent::getIterator();
    }

}
