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
    'initial_config_title'        => 'Import configuration (1/3)',
    'initial_config_text'         => 'To be able to import your file correctly, please validate the options below.',
    'initial_config_box'          => 'Basic CSV import configuration',
    'initial_header_help'         => 'Check this box if the first row of your CSV file are the column titles.',
    'initial_date_help'           => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'initial_delimiter_help'      => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'initial_import_account_help' => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',

    // roles config
    'roles_title'                 => 'Define each column\'s role',
    'roles_text'                  => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'roles_table'                 => 'Table',
    'roles_column_name'           => 'Name of column',
    'roles_column_example'        => 'Column example data',
    'roles_column_role'           => 'Column data meaning',
    'roles_do_map_value'          => 'Map these values',
    'roles_column'                => 'Column',
    'roles_no_example_data'       => 'No example data available',

    'roles_store' => 'Continue import',
    'roles_do_not_map'         => '(do not map)',

    // map data
    'map_title'                => 'Conectar datos de importación con datos de Firefly-III',
    'map_text'                 => 'En las siguientes tablas el valor de la izquierda muestra información encontrada en el CSV cargado. Es su tarea mapear este valor, si es posible, a un valor ya presente en su base de datos. Firefly respeterá este mapeo. Si no hay un valor hacia el cual mapear o no desea mapear un valor específico, no seleccione ninguno.',

    'field_value'          => 'Valor del campo',
    'field_mapped_to'      => 'Mapeado a',
    'store_column_mapping' => 'Guardar mapeo',

    // map things.


    'column__ignore'                => '(ignorar esta columna)',
    'column_account-iban'           => 'Caja de ahorro (CBU)',
    'column_account-id'             => 'ID de la caja de ahorro (coincide con Firefly)',
    'column_account-name'           => 'Caja de ahorro (nombre)',
    'column_amount'                 => 'Monto',
    'column_amount-comma-separated' => 'Monto (coma como separador de decimales)',
    'column_bill-id'                => 'ID de factura (coincide con Firefly)',
    'column_bill-name'              => 'Nombre de factura',
    'column_budget-id'              => 'ID de presupuesto (coincide con Firefly)',
    'column_budget-name'            => 'Nombre de presupuesto',
    'column_category-id'            => 'ID de categoría (coincide con Firefly)',
    'column_category-name'          => 'Nombre de categoría',
    'column_currency-code'          => 'Código de moneda (ISO 4217)',
    'column_currency-id'            => 'ID de moneda (coincide con Firefly)',
    'column_currency-name'          => 'Nombre de moneda (coincide con Firefly)',
    'column_currency-symbol'        => 'Símbolo de moneda (coincide con Firefly)',
    'column_date-interest'          => 'Fecha de cálculo de intereses',
    'column_date-book'              => 'Fecha de registro de transacción',
    'column_date-process'           => 'Fecha de proceso de transacción',
    'column_date-transaction'       => 'Fecha',
    'column_description'            => 'Descripción',
    'column_opposing-iban'          => 'Cuenta opuesta (CBU)',
    'column_opposing-id'            => 'ID de cuenta opuesta (coincide con Firefly)',
    'column_external-id'            => 'ID externo',
    'column_opposing-name'          => 'Cuenta opuesta (nombre)',
    'column_rabo-debet-credit'      => 'Indicador de débito/crédito específico de Rabobank',
    'column_ing-debet-credit'       => 'Indicador de débito/crédito específico de ING',
    'column_sepa-ct-id'             => 'ID de transferencia de crédito end-to-end de SEPA',
    'column_sepa-ct-op'             => 'Transferencia de crédito a cuenta opuesta SEPA',
    'column_sepa-db'                => 'Débito directo SEPA',
    'column_tags-comma'             => 'Etiquetas (separadas por comas)',
    'column_tags-space'             => 'Etiquetas (separadas por espacios)',
    'column_account-number'         => 'Caja de ahorro (número de cuenta)',
    'column_opposing-number'        => 'Cuenta opuesta (número de cuenta)',
];