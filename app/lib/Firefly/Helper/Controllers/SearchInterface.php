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
    public function searchTransactions(array $words);

    /**
     * @param array $words
     */
    public function searchAccounts(array $words);

    /**
     * @param array $words
     */
    public function searchCategories(array $words);

    /**
     * @param array $words
     */
    public function searchBudgets(array $words);

    /**
     * @param array $words
     */
    public function searchTags(array $words);

}