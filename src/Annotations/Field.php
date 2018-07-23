<?php
/**
 * Created by PhpStorm.
 * User: lvinkim
 * Date: 14/07/2018
 * Time: 8:59 PM
 */

namespace Lvinkim\MongoODM\Annotations;

/**
 * Class Field
 * @package Lvinkim\MongoODM\Annotations
 * @Annotation
 */
final class Field extends AbstractField
{
    const TYPES = [
        'string',
        'int',
        'float',
        'array',
        'date',
        'raw',
    ];
}