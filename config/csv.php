<?php
declare(strict_types = 1);


return [

    /*
     * Configuration for the CSV specifics.
     */
    'import_specifics' => [
        'RabobankDescription' => 'FireflyIII\Import\Specifics\RabobankDescription',
        'AbnAmroDescription'  => 'FireflyIII\Import\Specifics\AbnAmroDescription',
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
        '_ignore'           => [
            'mappable'        => false,
            'pre-process-map' => false,
            'field'           => 'ignored',
            'converter'       => 'Ignore',
            'mapper'          => null,


        ],
        'bill-id'           => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'bill',
            'converter'       => 'BillId',
            'mapper'          => 'Bills',
        ],
        'bill-name'         => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'bill',
            'converter'       => 'BillName',
            'mapper'          => 'Bills',
        ],
        'currency-id'       => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'currency',
            'converter'       => 'CurrencyId',
            'mapper'          => 'TransactionCurrencies',
        ],
        'currency-name'     => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CurrencyName',
            'field'           => 'currency',
            'mapper'          => 'TransactionCurrencies',
        ],
        'currency-code'     => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CurrencyCode',
            'field'           => 'currency',
            'mapper'          => 'TransactionCurrencies',
        ],
        'currency-symbol'   => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CurrencySymbol',
            'field'           => 'currency',
            'mapper'          => 'TransactionCurrencies',
        ],
        'description'       => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'description',
        ],
        'date-transaction'  => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date',
        ],
        'date-interest'         => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Date',
            'field'           => 'date-interest',
        ],
        'date-book'         => [
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
        'budget-id'         => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'BudgetId',
            'field'           => 'budget',
            'mapper'          => 'Budgets',
        ],
        'budget-name'       => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'BudgetName',
            'field'           => 'budget',
            'mapper'          => 'Budgets',
        ],
        'rabo-debet-credit' => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'RabobankDebetCredit',
            'field'           => 'amount-modifier',
        ],
        'ing-debet-credit'  => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'INGDebetCredit',
            'field'           => 'amount-modifier',
        ],
        'category-id'       => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CategoryId',
            'field'           => 'category',
            'mapper'          => 'Categories',
        ],
        'category-name'     => [
            'mappable'        => true,
            'pre-process-map' => false,
            'converter'       => 'CategoryName',
            'field'           => 'category',
            'mapper'          => 'Categories',
        ],
        'tags-comma'        => [
            'mappable'           => true,
            'pre-process-map'    => true,
            'pre-process-mapper' => 'TagsComma',
            'field'              => 'tags',
            'converter'          => 'TagsComma',
            'mapper'             => 'Tags',
        ],
        'tags-space'        => [
            'mappable'           => true,
            'pre-process-map'    => true,
            'pre-process-mapper' => 'TagsSpace',
            'field'              => 'tags',
            'converter'          => 'TagsSpace',
            'mapper'             => 'Tags',
        ],
        'account-id'        => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'asset-account-id',
            'converter'       => 'AccountId',
            'mapper'          => 'AssetAccounts',
        ],
        'account-name'      => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'asset-account-name',
            'converter'       => 'AssetAccountName',
            'mapper'          => 'AssetAccounts',
        ],
        'account-iban'      => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'asset-account-iban',
            'converter'       => 'AssetAccountIban',
            'mapper'          => 'AssetAccountIbans',

        ],
        'account-number'    => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'asset-account-number',
            'converter'       => 'AssetAccountNumber',
            'mapper'          => 'AssetAccounts',
        ],
        'opposing-id'       => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'opposing-account-id',
            'converter'       => 'AccountId',
            'mapper'          => 'OpposingAccounts',
        ],
        'opposing-name'     => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'opposing-account-name',
            'converter'       => 'OpposingAccountName',
            'mapper'          => 'OpposingAccounts',
        ],
        'opposing-iban'     => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'opposing-account-iban',
            'converter'       => 'OpposingAccountIban',
            'mapper'          => 'OpposingAccountIbans',
        ],
        'opposing-number'   => [
            'mappable'        => true,
            'pre-process-map' => false,
            'field'           => 'opposing-account-number',
            'converter'       => 'OpposingAccountNumber',
            'mapper'          => 'OpposingAccounts',
        ],
        'amount'            => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Amount',
            'field'           => 'amount',
        ],
        'sepa-ct-id'        => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'description',
        ],
        'sepa-ct-op'        => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'description',
        ],
        'sepa-db'           => [
            'mappable'        => false,
            'pre-process-map' => false,
            'converter'       => 'Description',
            'field'           => 'description',
        ],
    ],


    /*


    'specifix'        => [
        'RabobankDescription',
        'AbnAmroDescription',
        'Dummy'
    ],
    'post_processors' => [
        'Description',
        'Amount',
        'Currency',
        'Bill',
        'OpposingAccount', // must be after Amount!
        'AssetAccount',

    ],
    'roles'           => [
        '_ignore'           => [
            'mappable'  => false,
            'converter' => 'Ignore',
            'field'     => 'ignored',
        ],
        'bill-id'           => [
            'mappable'  => false,
            'field'     => 'bill',
            'converter' => 'BillId',
            'mapper'    => 'Bill',
        ],
        'bill-name'         => [
            'mappable'  => true,
            'converter' => 'BillName',
            'field'     => 'bill',
            'mapper'    => 'Bill',
        ],
        'currency-id'       => [
            'mappable'  => true,
            'converter' => 'CurrencyId',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'currency-name'     => [
            'mappable'  => true,
            'converter' => 'CurrencyName',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'currency-code'     => [
            'mappable'  => true,
            'converter' => 'CurrencyCode',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'currency-symbol'   => [
            'mappable'  => true,
            'converter' => 'CurrencySymbol',
            'field'     => 'currency',
            'mapper'    => 'TransactionCurrency'
        ],
        'description'       => [
            'mappable'  => false,
            'converter' => 'Description',
            'field'     => 'description',
        ],
        'date-transaction'  => [
            'mappable'  => false,
            'converter' => 'Date',
            'field'     => 'date',
        ],
        'date-rent'         => [
            'mappable'  => false,
            'converter' => 'Date',
            'field'     => 'date-rent',
        ],
        'budget-id'         => [
            'mappable'  => true,
            'converter' => 'BudgetId',
            'field'     => 'budget',
            'mapper'    => 'Budget',
        ],
        'budget-name'       => [
            'mappable'  => true,
            'converter' => 'BudgetName',
            'field'     => 'budget',
            'mapper'    => 'Budget',
        ],
        'rabo-debet-credit' => [
            'mappable'  => false,
            'converter' => 'RabobankDebetCredit',
            'field'     => 'amount-modifier',
        ],
        'ing-debet-credit' => [
            'mappable'  => false,
            'converter' => 'INGDebetCredit',
            'field'     => 'amount-modifier',
        ],
        'category-id'       => [
            'mappable'  => true,
            'converter' => 'CategoryId',
            'field'     => 'category',
            'mapper'    => 'Category',
        ],
        'category-name'     => [
            'mappable'  => true,
            'converter' => 'CategoryName',
            'field'     => 'category',
            'mapper'    => 'Category',
        ],
        'tags-comma'        => [
            'mappable'  => true,
            'field'     => 'tags',
            'converter' => 'TagsComma',
            'mapper'    => 'Tag',
        ],
        'tags-space'        => [
            'mappable'  => true,
            'field'     => 'tags',
            'converter' => 'TagsSpace',
            'mapper'    => 'Tag',
        ],
        'account-id'        => [
            'mappable'  => true,
            'mapper'    => 'AssetAccount',
            'field'     => 'asset-account-id',
            'converter' => 'AccountId'
        ],
        'account-name'      => [
            'mappable'  => true,
            'mapper'    => 'AssetAccount',
            'field'     => 'asset-account-name',
            'converter' => 'AssetAccountName'
        ],
        'account-iban'      => [
            'mappable'  => true,
            'converter' => 'AssetAccountIban',
            'field'     => 'asset-account-iban',
            'mapper'    => 'AssetAccount'
        ],
        'account-number'      => [
            'mappable'  => true,
            'converter' => 'AssetAccountNumber',
            'field'     => 'asset-account-number',
            'mapper'    => 'AssetAccount'
        ],
        'opposing-id'       => [
            'mappable'  => true,
            'field'     => 'opposing-account-id',
            'converter' => 'OpposingAccountId',
            'mapper'    => 'AnyAccount',
        ],
        'opposing-name'     => [
            'mappable'  => true,
            'field'     => 'opposing-account-name',
            'converter' => 'OpposingAccountName',
            'mapper'    => 'AnyAccount',
        ],
        'opposing-iban'     => [
            'mappable'  => true,
            'field'     => 'opposing-account-iban',
            'converter' => 'OpposingAccountIban',
            'mapper'    => 'AnyAccount',
        ],
        'opposing-number'     => [
            'mappable'  => true,
            'field'     => 'opposing-account-number',
            'converter' => 'OpposingAccountNumber',
            'mapper'    => 'AnyAccount',
        ],
        'amount'            => [
            'mappable'  => false,
            'converter' => 'Amount',
            'field'     => 'amount',
        ],
        'amount-comma-separated' => [
            'mappable'  => false,
            'converter' => 'AmountComma',
            'field'     => 'amount',
        ],
        'sepa-ct-id'        => [
            'mappable'  => false,
            'converter' => 'Description',
            'field'     => 'description',
        ],
        'sepa-ct-op'        => [
            'mappable'  => false,
            'converter' => 'Description',
            'field'     => 'description',
        ],
        'sepa-db'           => [
            'mappable'  => false,
            'converter' => 'Description',
            'field'     => 'description',
        ],
    ]


    */
];
