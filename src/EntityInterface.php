<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 14/07/2018
 * Time: 2:24 PM
 */

namespace Lvinkim\MongoODM;


/**
 * Entity 必须实现与 Mongodb Document 的转换方法
 * Interface EntityInterface
 * @package Lvinkim\MongoODM
 */
interface EntityInterface
{
    public function getDocument(): \stdClass;

    public function setByDocument(\stdClass $document);
}