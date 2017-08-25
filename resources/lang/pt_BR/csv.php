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
    'initial_title'                 => '',
    'initial_text'                  => '',
    'initial_box'                   => '',
    'initial_box_title'             => '',
    'initial_header_help'           => '',
    'initial_date_help'             => '',
    'initial_delimiter_help'        => '',
    'initial_import_account_help'   => '',
    'initial_submit'                => '',

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
    'map_text'                      => 'Nas tabelas a seguir, o valor à esquerda mostra informações encontradas no seu arquivo CSV carregado. É sua tarefa mapear esse valor, se possível, para um valor já presente em seu banco de dados. O Firefly vai se ater a esse mapeamento. Se não há nenhum valor para mapear, ou não quer mapear o valor específico, não selecione nada.',
    'map_field_value'               => '',
    'map_field_mapped_to'           => 'Mapeado para',
    'map_do_not_map'                => '(não mapear)',
    'map_submit'                    => '',

    // map things.
    'column__ignore'                => '(ignorar esta coluna)',
    'column_account-iban'           => 'Conta de Ativo (IBAN)',
    'column_account-id'             => 'ID da Conta de Ativo (correspondente Firefly)',
    'column_account-name'           => 'Conta de Ativo (nome)',
    'column_amount'                 => 'Quantia',
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