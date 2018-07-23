<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 15/07/2018
 * Time: 12:16 AM
 */

namespace Lvinkim\MongoODM\Annotations;


use Doctrine\Common\Annotations\Annotation;

/**
 * Class AbstractField
 * @package Lvinkim\MongoODM\Annotations
 */
class AbstractField extends Annotation
{
    public $id = false;
    public $type;
    public $target;
    public $options = [];
}