<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/30
 * Time: 5:41 PM
 */

namespace Lvinkim\MongoODM\Tests\App\Repository;


use Lvinkim\MongoODM\Repository;
use Lvinkim\MongoODM\Tests\App\Entity\Book;

class BookRepository extends Repository
{

    /**
     * 返回数据库中的表名, 例如: db.user
     * @return string
     */
    protected function getNamespace(): string
    {
        return 'asset.Book';
    }

    /**
     * 返回数据表的对应实体类名
     * @return string
     */
    protected function getEntityClassName(): string
    {
        return Book::class;
    }
}