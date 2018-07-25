<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 11:52 PM
 */

namespace Tests\App\Repository;


use Lvinkim\MongoODM\Repository;
use Tests\App\Entity\IsEntityWithString;

class IsEntityWithStringRepository extends Repository
{

    /**
     * 返回数据库中的表名, 例如: db.user
     * @return string
     */
    protected function getNamespace(): string
    {
        return 'test.entity_string';
    }

    /**
     * 返回数据表的对应实体类名
     * @return string
     */
    protected function getEntityClassName(): string
    {
        return IsEntityWithString::class;
    }
}