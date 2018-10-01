<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 01/10/2018
 * Time: 10:14 AM
 */

namespace Lvinkim\MongoODM\Tests\App\Repository;


use Lvinkim\MongoODM\Repository;

class PersonRepository extends Repository
{
    /**
     * 返回数据库中的表名, 例如: user
     * @return string
     */
    protected function collection(): string
    {
        return "person";
    }
}