<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/25
 * Time: 11:52 PM
 */

namespace Lvinkim\MongoODM\Tests\App\Repository;


use Lvinkim\MongoODM\Repository;
use Lvinkim\MongoODM\Tests\App\Entity\IsEntityWithString;

class IsEntityWithStringRepository extends Repository
{

    /**
     * 返回数据库中的表名, 例如: db.user
     * @return string
     */
    protected function collection(): string
    {
        return 'entity_string';
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