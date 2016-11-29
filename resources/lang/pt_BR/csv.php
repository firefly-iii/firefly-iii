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

declare(strict_types = 1);

return [

    'import_configure_title' => 'Configure sua importação',
    'import_configure_intro' => 'There are some options for your CSV import. Please indicate if your CSV file contains headers on the first column, and what the date format of your date-fields is. That might require some experimentation. The field delimiter is usually a ",", but could also be a ";". Check this carefully.',
    'import_configure_form'  => 'Opções básicas de importação CSV',
    'header_help'            => 'Verifique se a primeira linha do seu arquivo CSV está com os títulos de coluna',
    'date_help'              => 'Date time format in your CSV. Follow the format like <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">this page</a> indicates. The default value will parse dates that look like this: :dateExample.',
    'delimiter_help'         => 'Choose the field delimiter that is used in your input file. If not sure, comma is the safest option.',
    'import_account_help'    => 'Se seu arquivo CSV NÃO contém informações sobre sua(s) conta(s) ativa(s), use este combobox para selecionar para qual conta pertencem as transações no CSV.',
    'upload_not_writeable'   => 'Na caixa cinza contém um caminho de arquivo. Deve ser possível gravar nele. Por favor, certifique-se de que é.',

    // roles
    'column_roles_title'     => 'Definir papeis da coluna',
    'column_roles_table'     => 'Tabela',
    'column_name'            => 'Nome da coluna',
    'column_example'         => 'Dados de exemplo da coluna',
    'column_role'            => 'Significado de dados de coluna',
    'do_map_value'           => 'Mapear esses valores',
    'column'                 => 'Coluna',
    'no_example_data'        => 'Não há dados de exemplo disponíveis',
    'store_column_roles'     => 'Continuar a importação',
    'do_not_map'             => '(não mapear)',
    'map_title'              => 'Conectar dados importados para dados do Firefly III',
    'map_text'               => 'In the following tables, the left value shows you information found in your uploaded CSV file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',

    'field_value'          => 'Valor do campo',
    'field_mapped_to'      => 'Mapeado para',
    'store_column_mapping' => 'Mapear armazenamento',

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