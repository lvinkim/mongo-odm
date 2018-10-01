<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 14/07/2018
 * Time: 8:56 PM
 */

namespace Lvinkim\MongoODM\Tests\App\Entity;

use Lvinkim\MongoODM\Annotations as ODM;
use MongoDB\BSON\ObjectId;
use Lvinkim\MongoODM\Tests\App\Entity\Embed\Company;
use Lvinkim\MongoODM\Tests\App\Entity\Embed\Member;

/**
 * Class User
 * @package Lvinkim\MongoODM\Tests\App\Entity
 * @ODM\Entity()
 */
class User
{
    /**
     * @var ObjectId
     * @ODM\Id
     */
    private $id;

    /**
     * 名称
     * @var string
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @var bool
     * @ODM\Field(type="bool")
     */
    private $status;

    /**
     * 年龄
     * @var int
     * @ODM\Field(type="int")
     */
    private $age;
    /**
     * 体重
     * @var float
     * @ODM\Field(type="float")
     */
    private $weight;
    /**
     * 标签
     * @var array
     * @ODM\Field(type="array")
     */
    private $tags;
    /**
     * 出生日期
     * @var \DateTime
     * @ODM\Field(type="date")
     */
    private $birth;
    /**
     * 所在公司
     * @var Company
     * @ODM\EmbedOne(target="Lvinkim\MongoODM\Tests\App\Entity\Embed\Company")
     */
    private $company;
    /**
     * 家庭成员
     * @var Member[]
     * @ODM\EmbedMany(target="Lvinkim\MongoODM\Tests\App\Entity\Embed\Member")
     */
    private $families;
    /**
     * 备注信息
     * @var mixed
     * @ODM\Field(type="raw")
     */
    private $remark;

    /**
     * @return ObjectId
     */
    public function getId(): ?ObjectId
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
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
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @return \DateTime
     */
    public function getBirth(): \DateTime
    {
        return $this->birth;
    }

    /**
     * @param \DateTime $birth
     */
    public function setBirth(\DateTime $birth): void
    {
        $this->birth = $birth;
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * @return Member[]
     */
    public function getFamilies(): array
    {
        return $this->families;
    }

    /**
     * @param Member[] $families
     */
    public function setFamilies(array $families): void
    {
        $this->families = $families;
    }

    /**
     * @return mixed
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param mixed $remark
     */
    public function setRemark($remark): void
    {
        $this->remark = $remark;
    }

    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }
}