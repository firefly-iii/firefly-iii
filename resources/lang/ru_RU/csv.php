<?php
/**
 * csv.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

return [

    // initial config
    'initial_title'                 => 'Импорт данных (1/3) - Подготовка к импорту CSV',
    'initial_text'                  => 'Чтобы импорт данных прошёл успешно, пожалуйста проверьте несколько параметров.',
    'initial_box'                   => 'Основные параметры импорта CSV',
    'initial_box_title'             => '',
    'initial_header_help'           => 'Установите этот флажок, если первая строка CSV-файла содержит заголовки столбцов.',
    'initial_date_help'             => 'Формат даты и времени в вашем CSV-файле. Придерживайтесь формата, описанного <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">на этой</a> странице. По умолчанию дату будут анализироваться на соответствие такому формату: :dateExample.',
    'initial_delimiter_help'        => 'Выберите разделитель полей, который используется в вашем файле. Если вы не уверены, помните, что запятая - это самый безопасный вариант.',
    'initial_import_account_help'   => 'Если ваш CSV-файл НЕ СОДЕРЖИТ информацию о ваших счётах, укажите счета для всех транзакций, выбрав подходящие из выпадающего списка.',
    'initial_submit'                => 'Перейти к шагу 2/3',

    // roles config
    'roles_title'                   => '',
    'roles_text'                    => '',
    'roles_table'                   => '',
    'roles_column_name'             => '',
    'roles_column_example'          => '',
    'roles_column_role'             => '',
    'roles_do_map_value'            => '',
    'roles_column'                  => '',
    'roles_no_example_data'         => '',
    'roles_submit'                  => '',
    'roles_warning'                 => '',

    // map data
    'map_title'                     => '',
    'map_text'                      => '',
    'map_field_value'               => '',
    'map_field_mapped_to'           => '',
    'map_do_not_map'                => '',
    'map_submit'                    => '',

    // map things.
    'column__ignore'                => '',
    'column_account-iban'           => '',
    'column_account-id'             => '',
    'column_account-name'           => '',
    'column_amount'                 => '',
    'column_amount-comma-separated' => '',
    'column_bill-id'                => '',
    'column_bill-name'              => '',
    'column_budget-id'              => '',
    'column_budget-name'            => '',
    'column_category-id'            => '',
    'column_category-name'          => '',
    'column_currency-code'          => '',
    'column_currency-id'            => '',
    'column_currency-name'          => '',
    'column_currency-symbol'        => '',
    'column_date-interest'          => '',
    'column_date-book'              => '',
    'column_date-process'           => '',
    'column_date-transaction'       => '',
    'column_description'            => '',
    'column_opposing-iban'          => '',
    'column_opposing-id'            => '',
    'column_external-id'            => '',
    'column_opposing-name'          => '',
    'column_rabo-debet-credit'      => '',
    'column_ing-debet-credit'       => '',
    'column_sepa-ct-id'             => '',
    'column_sepa-ct-op'             => '',
    'column_sepa-db'                => '',
    'column_tags-comma'             => '',
    'column_tags-space'             => '',
    'column_account-number'         => '',
    'column_opposing-number'        => '',
];