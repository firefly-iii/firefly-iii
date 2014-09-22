<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 21/09/14
 * Time: 20:58
 */

namespace Firefly\Helper\Controllers;

/**
 * Interface SearchInterface
 *
 * @package Firefly\Helper\Controllers
 */
interface SearchInterface
{
    /**
     * @param array $words
     */
    public function transactions(array $words);
} 