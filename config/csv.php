<?php
return [
    'roles' => [
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
            'converter' => 'BillId'
        ],
        'bill-name'         => [
            'name'      => 'Bill name',
            'mappable'  => true,
            'converter' => 'BillName',
            'field'     => 'bill',
        ],
        'currency-id'       => [
            'name'     => 'Currency ID (matching Firefly)',
            'mappable' => true,
        ],
        'currency-name'     => [
            'name'     => 'Currency name (matching Firefly)',
            'mappable' => true,
        ],
        'currency-code'     => [
            'name'      => 'Currency code (ISO 4217)',
            'mappable'  => true,
            'converter' => 'CurrencyCode',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'currency-symbol'   => [
            'name'     => 'Currency symbol (matching Firefly)',
            'mappable' => true,
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
            'name'     => 'Budget ID (matching Firefly)',
            'mappable' => true,
        ],
        'budget-name'       => [
            'name'     => 'Budget name',
            'mappable' => true,
        ],
        'rabo-debet-credit' => [
            'name'      => 'Rabobank specific debet/credit indicator',
            'mappable'  => false,
            'converter' => 'RabobankDebetCredit',
            'field'     => 'amount-modifier',
        ],
        'category-id'       => [
            'name'     => 'Category ID (matching Firefly)',
            'mappable' => true,
        ],
        'category-name'     => [
            'name'     => 'Category name',
            'mappable' => true,
        ],
        'tags-comma'        => [
            'name'     => 'Tags (comma separated)',
            'mappable' => true,
        ],
        'tags-space'        => [
            'name'     => 'Tags (space separated)',
            'mappable' => true,
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
            'name'     => 'Opposing account account ID (matching Firefly)',
            'mappable' => true,
        ],
        'opposing-name'     => [
            'name'      => 'Opposing account name',
            'mappable'  => true,
            'converter' => 'OpposingName',
            'field'     => 'opposing-account'
        ],
        'opposing-iban'     => [
            'name'     => 'Opposing account IBAN',
            'mappable' => true,
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