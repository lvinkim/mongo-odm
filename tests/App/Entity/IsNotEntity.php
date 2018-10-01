<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 4:36 PM
 */

namespace Lvinkim\MongoODM\Tests\App\Entity;

use MongoDB\BSON\ObjectId;


/**
 * Class IsNotEntity
 * @package Lvinkim\MongoODM\Tests\App\Entity
 */
class IsNotEntity
{
    /**
     * @var ObjectId
     */
    public $_id;

    public $name;
}