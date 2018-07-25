<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 11:12 PM
 */

namespace Tests\App\Entity;


use Lvinkim\MongoODM\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/**
 * Class IsEntityWithString
 * @package Tests\App\Entity
 */
class IsNotEntityWithString
{
    /**
     * @var ObjectId
     */
    public $_id;

    /**
     * @var string
     */
    public $name;

}