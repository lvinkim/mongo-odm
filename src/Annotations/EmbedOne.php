<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 14/07/2018
 * Time: 9:51 PM
 */

namespace Lvinkim\MongoODM\Annotations;

/**
 * Class EmbedOne
 * @package Lvinkim\MongoODM\Annotations
 * @Annotation
 */
final class EmbedOne extends AbstractField
{
    public $type = 'embedOne';
}