<?php
return [
    'specifix'        => [
        'RabobankDescription'
    ],
    'post_processors' => [
        'Description',
        'Amount',
        'Currency',
        'Bill',
        'OpposingAccount', // must be after Amount!

    ],
    'roles'           => [
        '_ignore'           => [
            'name'      => '(ignore this column)',
            'mappable'  => false,
            'converter' => 'Ignore',
            'field'     => 'ignored',
        ],
        'bill-id'           => [
            'name'      => 'Bill ID (matching Firefly)',
            'mappable'  => false,
            'field'     => 'bill',
            'converter' => 'BillId',
            'mapper'    => 'Bill',
        ],
        'bill-name'         => [
            'name'      => 'Bill name',
            'mappable'  => true,
            'converter' => 'BillName',
            'field'     => 'bill',
            'mapper'    => 'Bill',
        ],
        'currency-id'       => [
            'name'      => 'Currency ID (matching Firefly)',
            'mappable'  => true,
            'converter' => 'CurrencyId',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'currency-name'     => [
            'name'      => 'Currency name (matching Firefly)',
            'mappable'  => true,
            'converter' => 'CurrencyName',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'currency-code'     => [
            'name'      => 'Currency code (ISO 4217)',
            'mappable'  => true,
            'converter' => 'CurrencyCode',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'currency-symbol'   => [
            'name'      => 'Currency symbol (matching Firefly)',
            'mappable'  => true,
            'converter' => 'CurrencySymbol',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'description'       => [
            'name'      => 'Description',
            'mappable'  => false,
            'converter' => 'Description',
            'field'     => 'description',
        ],
        'date-transaction'  => [
            'name'      => 'Date',
            'mappable'  => false,
            'converter' => 'Date',
            'field'     => 'date',
        ],
        'date-rent'         => [
            'name'      => 'Rent calculation date',
            'mappable'  => false,
            'converter' => 'Date',
            'field'     => 'date-rent',
        ],
        'budget-id'         => [
            'name'      => 'Budget ID (matching Firefly)',
            'mappable'  => true,
            'converter' => 'BudgetId',
            'field'     => 'budget',
            'mapper'    => 'Budget',
        ],
        'budget-name'       => [
            'name'      => 'Budget name',
            'mappable'  => true,
            'converter' => 'BudgetName',
            'field'     => 'budget',
            'mapper'    => 'Budget',
        ],
        'rabo-debet-credit' => [
            'name'      => 'Rabobank specific debet/credit indicator',
            'mappable'  => false,
            'converter' => 'RabobankDebetCredit',
            'field'     => 'amount-modifier',
        ],
        'category-id'       => [
            'name'      => 'Category ID (matching Firefly)',
            'mappable'  => true,
            'converter' => 'CategoryId',
            'field'     => 'category',
            'mapper'    => 'Category',
        ],
        'category-name'     => [
            'name'      => 'Category name',
            'mappable'  => true,
            'converter' => 'CategoryName',
            'field'     => 'category',
            'mapper'    => 'Category',
        ],
        'tags-comma'        => [
            'name'      => 'Tags (comma separated)',
            'mappable'  => true,
            'field'     => 'tags',
            'converter' => 'TagsComma',
            'mapper'    => 'Tag',
        ],
        'tags-space'        => [
            'name'      => 'Tags (space separated)',
            'mappable'  => true,
            'field'     => 'tags',
            'converter' => 'TagsSpace',
            'mapper'    => 'Tag',
        ],
        'account-id'        => [
            'name'      => 'Asset account ID (matching Firefly)',
            'mappable'  => true,
            'mapper'    => 'AssetAccount',
            'field'     => 'asset-account',
            'converter' => 'AccountId'
        ],
        'account-name'      => [
            'name'      => 'Asset account name',
            'mappable'  => true,
            'mapper'    => 'AssetAccount',
            'field'     => 'asset-account',
            'converter' => 'AssetAccountName'
        ],
        'account-iban'      => [
            'name'      => 'Asset account IBAN',
            'mappable'  => true,
            'converter' => 'AssetAccountIban',
            'field'     => 'asset-account',
            'mapper'    => 'AssetAccount'
        ],
        'opposing-id'       => [
            'name'      => 'Opposing account account ID (matching Firefly)',
            'mappable'  => true,
            'field'     => 'opposing-account-id',
            'converter' => 'OpposingAccountId',
            'mapper'    => 'AnyAccount',
        ],
        'opposing-name'     => [
            'name'      => 'Opposing account name',
            'mappable'  => true,
            'field'     => 'opposing-account-name',
            'converter' => 'OpposingAccountName',
            'mapper'    => 'AnyAccount',
        ],
        'opposing-iban'     => [
            'name'      => 'Opposing account IBAN',
            'mappable'  => true,
            'field'     => 'opposing-account-iban',
            'converter' => 'OpposingAccountIban',
            'mapper'    => 'AnyAccount',
        ],
        'amount'            => [
            'name'      => 'Amount',
            'mappable'  => false,
            'converter' => 'Amount',
            'field'     => 'amount',
        ],
        'sepa-ct-id'        => [
            'name'      => 'SEPA Credit Transfer end-to-end ID',
            'mappable'  => false,
            'converter' => 'Description',
            'field'     => 'description',
        ],
        'sepa-ct-op'        => [
            'name'      => 'SEPA Credit Transfer opposing account',
            'mappable'  => false,
            'converter' => 'Description',
            'field'     => 'description',
        ],
        'sepa-db'           => [
            'name'      => 'SEPA Direct Debet',
            'mappable'  => false,
            'converter' => 'Description',
            'field'     => 'description',
        ],
    ]
];