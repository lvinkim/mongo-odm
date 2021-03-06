<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 23/07/2018
 * Time: 11:08 PM
 */

namespace Lvinkim\MongoODM\Tests\App\Repository;

use Lvinkim\MongoODM\Repository;
use Lvinkim\MongoODM\Tests\App\Entity\User;

class UserRepository extends Repository
{
    /**
     * 返回数据库中的表名, 例如: user
     * @return string
     */
    protected function collection(): string
    {
        return "user";
    }

    /**
     * @return string
     */
    protected function getEntityClassName()
    {
        return User::class;
    }
}