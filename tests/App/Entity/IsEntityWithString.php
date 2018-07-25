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
 * @ODM\Entity()
 */
class IsEntityWithString
{
    /**
     * @var ObjectId
     * @ODM\Id
     */
    private $id;
    /**
     * @var string
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @return ObjectId
     */
    public function getId(): ObjectId
    {
        return $this->id;
    }

    /**
     * @param ObjectId $id
     */
    public function setId(ObjectId $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}