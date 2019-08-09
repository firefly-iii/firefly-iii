<?php

/**
 * csv.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use FireflyIII\Import\Specifics\AbnAmroDescription;
use FireflyIII\Import\Specifics\IngDescription;
use FireflyIII\Import\Specifics\PresidentsChoice;
use FireflyIII\Import\Specifics\SnsDescription;
use FireflyIII\Import\Specifics\Belfius;
use FireflyIII\Import\Specifics\IngBelgium;

return [

    /*
     * Configuration for the CSV specifics.
     */
    'import_specifics' => [
        'IngDescription'     => IngDescription::class,
        'AbnAmroDescription' => AbnAmroDescription::class,
        'SnsDescription'     => SnsDescription::class,
        'PresidentsChoice'   => PresidentsChoice::class,
        'Belfius'            => Belfius::class,
        'IngBelgium'         => IngBelgium::class
    ],

    /*
     * Configuration for possible column roles.
     *
     * The key is the short name for the column role. There are five values, which mean this:
     *
     * 'mappable'
     * Whether or not the value in the CSV column can be linked to an existing value in your
     * Firefly database. For example: account names can be linked to existing account names you have already
     * so double entries cannot occur. This process is called "mapping". You have to make each unique value in your
     * CSV file to an existing entry in your database. For example, map all account names in your CSV file to existing
     * accounts. If you have an entry that does not exist in your database, you can set Firefly to ignore it, and it will
     * create it.
     *
     * 'pre-process-map'
     * In the case of tags, there are multiple values in one csv column (for example: "expense groceries snack" in one column).
     * This means the content of the column must be "pre processed" aka split in parts so the importer can work with the data.
     *
     * 'pre-process-mapper'
     * This is the class that will actually do the pre-processing.
     *
     * 'field'
     * I don't believe this value is used any more, but I am not sure.
     *
     * 'converter'
     * The converter is a class in app/Import/Converter that converts the given value into an object Firefly understands.
     * The CategoryName converter can convert a category name into an actual category. This converter will take a mapping
     * into account: if you mapped "Groceries" to category "Groceries" the converter will simply return "Groceries" instead of
     * trying to make a new category also named Groceries.
     *
     * 'mapper'
     * When you map data (see "mappable") you need a list of stuff you can map to. If you say a certain column is mappable
     * and the column contains "category names", the mapper will be "Category" and it will give you a list of possible categories.
     * This way the importer always presents you with a valid list of things to map to.
     *
     *
     *
     */
    'import_roles'     => [
        '_ignore'               => [
            'mappable'        => false,
            'pre-process-map' => false,
            'field'           => 'ignored',
            'converter'       => 'Ignore',
            'mapper'          => null,


        ],
        'bill-id'               => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'bill',
            'converter'       => 'BillId',
            'mapper'          => 'Bills',
        ],
        'note'                  => [
            'mappable'        => false,
            'pre-process-map' => false,
            'field'           => 'note',
            'converter'       => 'Note',
        ],
        'bill-name'             => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'bill',
            'converter'       => 'BillName',
            'mapper'          => 'Bills',
        ],
        'currency-id'           => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'currency',
            'converter'       => 'CurrencyId',
            'mapper'          => 'TransactionCurrencies',
        ],
        'currency-name'         => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CurrencyName',
            'field'           => 'currency',
            'mapper'          => 'TransactionCurrencies',
        ],
        'currency-code'         => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CurrencyCode',
            'field'           => 'currency',
            'mapper'          => 'TransactionCurrencies',
        ],
        'foreign-currency-code' => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CurrencyCode',
            'field'           => 'foreign_currency',
            'mapper'          => 'TransactionCurrencies',
        ],
        'external-id'           => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'ExternalId',
            'field'           => 'external-id',
        ],

        'currency-symbol'      => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CurrencySymbol',
            'field'           => 'currency',
            'mapper'          => 'TransactionCurrencies',
        ],
        'description'          => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'description',
        ],
        'date-transaction'     => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date',
        ],
        'date-interest'        => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date-interest',
        ],
        'date-book'            => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date-book',
        ],
        'date-process'         => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date-process',
        ],
        'date-due'             => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date-due',
        ],
        'date-payment'         => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date-payment',
        ],
        'date-invoice'         => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date-invoice',
        ],
        'budget-id'            => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'BudgetId',
            'field'           => 'budget',
            'mapper'          => 'Budgets',
        ],
        'budget-name'          => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'BudgetName',
            'field'           => 'budget',
            'mapper'          => 'Budgets',
        ],
        'rabo-debit-credit'    => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'BankDebitCredit',
            'field'           => 'amount-modifier',
        ],
        'ing-debit-credit'     => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'BankDebitCredit',
            'field'           => 'amount-modifier',
        ],
        'generic-debit-credit' => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'BankDebitCredit',
            'field'           => 'amount-modifier',
        ],
        'category-id'          => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CategoryId',
            'field'           => 'category',
            'mapper'          => 'Categories',
        ],
        'category-name'        => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CategoryName',
            'field'           => 'category',
            'mapper'          => 'Categories',
        ],
        'tags-comma'           => [
            'mappable'           => false,
            'pre-process-map'    => true,
            'pre-process-mapper' => 'TagsComma',
            'field'              => 'tags',
            'converter'          => 'TagsComma',
            'mapper'             => 'Tags',
        ],
        'tags-space'           => [
            'mappable'           => false,
            'pre-process-map'    => true,
            'pre-process-mapper' => 'TagsSpace',
            'field'              => 'tags',
            'converter'          => 'TagsSpace',
            'mapper'             => 'Tags',
        ],
        'account-id'           => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'asset-account-id',
            'converter'       => 'AccountId',
            'mapper'          => 'AssetAccounts',
        ],
        'account-name'         => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'asset-account-name',
            'converter'       => 'AssetAccountName',
            'mapper'          => 'AssetAccounts',
        ],
        'account-iban'         => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'asset-account-iban',
            'converter'       => 'AssetAccountIban',
            'mapper'          => 'AssetAccountIbans',

        ],
        'account-number'       => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'asset-account-number',
            'converter'       => 'AssetAccountNumber',
            'mapper'          => 'AssetAccounts',
        ],
        'account-bic'          => [
            'mappable'        => false,
            'pre-process-map' => false,
            'field'           => 'asset-account-bic',
            'converter'       => 'AccountBic',
        ],
        'opposing-id'          => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'opposing-account-id',
            'converter'       => 'AccountId',
            'mapper'          => 'OpposingAccounts',
        ],
        'opposing-bic'         => [
            'mappable'        => false,
            'pre-process-map' => false,
            'field'           => 'opposing-account-bic',
            'converter'       => 'AccountBic',
        ],
        'opposing-name'        => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'opposing-account-name',
            'converter'       => 'OpposingAccountName',
            'mapper'          => 'OpposingAccounts',
        ],
        'opposing-iban'        => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'opposing-account-iban',
            'converter'       => 'OpposingAccountIban',
            'mapper'          => 'OpposingAccountIbans',
        ],
        'opposing-number'      => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'opposing-account-number',
            'converter'       => 'OpposingAccountNumber',
            'mapper'          => 'OpposingAccounts',
        ],
        'amount'               => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Amount',
            'field'           => 'amount',
        ],
        'amount_debit'         => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'AmountDebit',
            'field'           => 'amount_debit',
        ],
        'amount_credit'        => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'AmountCredit',
            'field'           => 'amount_credit',
        ],
        'amount_negated'       => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'AmountNegated',
            'field'           => 'amount_negated',
        ],
        'amount_foreign'       => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Amount',
            'field'           => 'amount_foreign',
        ],

        // SEPA end to end ID
        'sepa_ct_id'           => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'sepa_ct_id',
        ],
        // SEPA opposing account identifier
        'sepa_ct_op'           => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'sepa_ct_op',
        ],
        // SEPA Direct Debit Mandate Identifier
        'sepa_db'              => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'sepa_db',
        ],
        // SEPA clearing code
        'sepa_cc'              => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'sepa_cc',
        ],
        // SEPA country
        'sepa_country'         => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'sepa_country',
        ],
        // SEPA external purpose
        'sepa_ep'              => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'sepa_ep',
        ],
        // SEPA creditor identifier
        'sepa_ci'              => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'sepa_ci',
        ],
        // SEPA Batch ID
        'sepa_batch_id'        => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'sepa_batch',
        ],
        // Internal reference
        'internal-reference'   => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'internal_reference',
        ],
    ],

    // number of example rows:
    'example_rows'     => 5,
];
