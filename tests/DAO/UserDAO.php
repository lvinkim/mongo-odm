<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 23/07/2018
 * Time: 11:08 PM
 */

namespace Tests\DAO;

use Lvinkim\MongoODM\EntityDAO;
use Tests\Entity\User;

class UserDAO extends EntityDAO
{

    /**
     * 返回数据库中的表名, 例如: db.user
     * @return string
     */
    protected function getNamespace(): string
    {
        return 'test.user';
    }

    /**
     * 返回数据表的对应实体类名
     * @return string
     */
    protected function getEntity(): string
    {
        return User::class;
    }
}