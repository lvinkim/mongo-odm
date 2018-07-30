<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 2018/7/30
 * Time: 5:37 PM
 */

namespace Tests\App\Entity;

use Lvinkim\MongoODM\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/**
 * Class Book
 * @package Tests\App\Entity
 * @ODM\Entity()
 */
class Book
{
    /**
     * @var ObjectId
     * @ODM\Id()
     */
    public $_id;

    /**
     * 书的sku
     * @var string
     * @ODM\Field(type="string")
     */
    public $ISBN;

    /**
     * 书的名称
     * @var string
     * @ODM\Field(type="string")
     */
    public $name;

    /**
     * 书的描述
     * @var string
     * @ODM\Field(type="string")
     */
    public $desc;

    /**
     * 书的封面id,
     * @var string
     * @ODM\Field(type="string")
     */
    public $img_id;

    /**
     * 书图片的url
     * @var string
     * @ODM\Field(type="string")
     */
    public $img_url;

    /**
     * 书的贡献者
     * @var string
     * @ODM\Field(type="string")
     */
    public $contributor;

    /**
     * 书的借用者
     * @var string
     * @ODM\Field(type="string")
     */
    public $borrower;

    /**
     * 书的种类
     * @var string
     * @ODM\Field(type="string")
     */
    public $type;

    /**
     * 书的借出时间
     * @var \DateTime
     * @ODM\Field(type="date")
     */
    public $borrow_time;

    /**
     * 书的应归还期
     * @var \DateTime
     * @ODM\Field(type="date")
     */
    public $return_time;

    /**
     * 书的推荐者
     * @var string
     * @ODM\Field(type="string")
     */
    public $presenter;

    /**
     * 书的状态
     * @var int
     * @ODM\Field(type="int")
     */
    public $status = -1;

    /**
     * 书的推荐理由
     * @var string
     * @ODM\Field(type="string")
     */
    public $comment;

    /**
     * 书的借出次数
     * @var int
     * @ODM\Field(type="int")
     */
    public $borrow_sum = 0;

    /**
     * 图书入库时间
     * @var \DateTime
     * @ODM\Field(type="date")
     */
    public $create_time;
}