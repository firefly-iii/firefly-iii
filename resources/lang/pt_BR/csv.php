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
    'initial_title'                 => 'Import setup (1/3) - Basic CSV import setup',
    'initial_text'                  => 'To be able to import your file correctly, please validate the options below.',
    'initial_box'                   => 'Basic CSV import setup',
    'initial_box_title'             => 'Basic CSV import setup options',
    'initial_header_help'           => 'Check this box if the first row of your CSV file are the column titles.',
    'initial_date_help'             => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'initial_delimiter_help'        => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'initial_import_account_help'   => 'If your CSV file does NOT contain information about your asset account(s), use this dropdown to select to which account the transactions in the CSV belong to.',
    'initial_submit'                => 'Continue with step 2/3',

    // new options:
    'apply_rules_title'             => 'Apply rules',
    'apply_rules_description'       => 'Apply your rules. Note that this slows the import significantly.',
    'match_bills_title'             => 'Match bills',
    'match_bills_description'       => 'Match your bills to newly created withdrawals. Note that this slows the import significantly.',

    // roles config
    'roles_title'                   => 'Import setup (2/3) - Define each column\'s role',
    'roles_text'                    => 'Each column in your CSV file contains certain data. Please indicate what kind of data the importer should expect. The option to "map" data means that you will link each entry found in the column to a value in your database. An often mapped column is the column that contains the IBAN of the opposing account. That can be easily matched to IBAN\'s present in your database already.',
    'roles_table'                   => 'Table',
    'roles_column_name'             => 'Name of column',
    'roles_column_example'          => 'Column example data',
    'roles_column_role'             => 'Column data meaning',
    'roles_do_map_value'            => 'Map these values',
    'roles_column'                  => 'Column',
    'roles_no_example_data'         => 'No example data available',
    'roles_submit'                  => 'Continue with step 3/3',
    'roles_warning'                 => 'At the very least, mark one column as the amount-column. It is advisable to also select a column for the description, date and the opposing account.',

    // map data
    'map_title'                     => 'Import setup (3/3) - Connect import data to Firefly III data',
    'map_text'                      => 'Nas tabelas a seguir, o valor à esquerda mostra informações encontradas no seu arquivo CSV carregado. É sua tarefa mapear esse valor, se possível, para um valor já presente em seu banco de dados. O Firefly vai se ater a esse mapeamento. Se não há nenhum valor para mapear, ou não quer mapear o valor específico, não selecione nada.',
    'map_field_value'               => 'Field value',
    'map_field_mapped_to'           => 'Mapeado para',
    'map_do_not_map'                => '(não mapear)',
    'map_submit'                    => 'Start the import',

    // map things.
    'column__ignore'                => '(ignorar esta coluna)',
    'column_account-iban'           => 'Conta de Ativo (IBAN)',
    'column_account-id'             => 'ID da Conta de Ativo (correspondente Firefly)',
    'column_account-name'           => 'Conta de Ativo (nome)',
    'column_amount'                 => 'Quantia',
    'column_amount_debet'           => 'Amount (debet column)',
    'column_amount_credit'          => 'Amount (credit column)',
    'column_amount-comma-separated' => 'Quantia (vírgula como separador decimal)',
    'column_bill-id'                => 'ID Fatura (correspondente Firefly)',
    'column_bill-name'              => 'Nome da Fatura',
    'column_budget-id'              => 'ID do Orçamento (correspondente Firefly)',
    'column_budget-name'            => 'Nome do Orçamento',
    'column_category-id'            => 'ID da Categoria (correspondente Firefly)',
    'column_category-name'          => 'Nome da Categoria',
    'column_currency-code'          => 'Código da Moeda (ISO 4217)',
    'column_currency-id'            => 'ID da Moeda (correspondente Firefly)',
    'column_currency-name'          => 'Nome da Moeda (correspondente Firefly)',
    'column_currency-symbol'        => 'Símbolo da Moeda (correspondente Firefly)',
    'column_date-interest'          => 'Data de cálculo de juros',
    'column_date-book'              => 'Data da reserva de transação',
    'column_date-process'           => 'Data do processo de transação',
    'column_date-transaction'       => 'Data',
    'column_description'            => 'Descrição',
    'column_opposing-iban'          => 'Conta contrária (IBAN)',
    'column_opposing-id'            => 'ID da Conta Cotrária (correspondente Firefly)',
    'column_external-id'            => 'ID Externo',
    'column_opposing-name'          => 'Conta contrária (nome)',
    'column_rabo-debet-credit'      => 'Indicador de débito/crédito do Rabobank',
    'column_ing-debet-credit'       => 'Indicador de débito/crédito do ING',
    'column_sepa-ct-id'             => 'ID da Transferência de Crédito fim-a-fim SEPA',
    'column_sepa-ct-op'             => 'Transferência de Crédito SEPA conta contrária',
    'column_sepa-db'                => 'SEPA Débito Direto',
    'column_tags-comma'             => 'Tags (separadas por vírgula)',
    'column_tags-space'             => 'Tags (separadas por espaço)',
    'column_account-number'         => 'Conta de ativo (número da conta)',
    'column_opposing-number'        => 'Conta Contrária (número da conta)',
];
