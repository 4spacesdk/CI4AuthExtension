<?php namespace AuthExtension\Entities;

use OrmExtension\Extensions\Entity;

/**
 * Class OAuthJwt
 * @package AuthExtension
 * @property bool $is_default
 * @property string $subject
 * @property string $public_key
 */
class OAuthJwt extends Entity {

    /**
     * @return \ArrayIterator|\RestExtension\Core\Entity[]|\Traversable|OAuthJwt[]
     */
    public function getIterator(): \ArrayIterator {
        return parent::getIterator();
    }

}
