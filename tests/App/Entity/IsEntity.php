<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 4:36 PM
 */

namespace Tests\App\Entity;

use Lvinkim\MongoODM\Annotations as ODM;
use MongoDB\BSON\ObjectId;


/**
 * Class IsEntity
 * @package Tests\App\Entity
 * @ODM\Entity
 */
class IsEntity
{
    const STATUS_NORMAL = 'normal';
    const STATUS_ABNORMAL = 'abnormal';

    static public $statuses = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_ABNORMAL => '不正常',
    ];

    /**
     * @var ObjectId
     * @ODM\Id()
     */
    public $id;

    /**
     * @var string
     * @ODM\Field(type="string")
     */
    public $name;

    /**
     * @var int
     * @ODM\Field(type="int")
     */
    private $age;

    /**
     * @var float
     * @ODM\Field(type="float")
     */
    protected $weight;

    /**
     * 没有使用 Field 声明，不会持久化到 mongodb
     * @var mixed
     */
    private $_parent;

    /**
     * @return int
     */
    public function getAge(): int
    {
        return $this->age;
    }

    /**
     * @param int $age
     */
    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent): void
    {
        $this->_parent = $parent;
    }
}