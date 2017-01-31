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
    'import_configure_intro' => 'Existem algumas opções para sua importação CSV. Por favor, indique se o seu arquivo CSV contém cabeçalhos na primeira coluna, e qual o formato de data de seus campos de data. Isso pode exigir alguma experimentação. O delimitador de campo é geralmente um ",", mas também poderia ser um ";". Verifique isto cuidadosamente.',
    'import_configure_form'  => 'Opções básicas de importação CSV',
    'header_help'            => 'Verifique se a primeira linha do seu arquivo CSV está com os títulos de coluna',
    'date_help'              => 'Formato de data e hora em seu CSV. Siga o formato como indica <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">esta página</a>. O valor padrão analisará datas que se parecem com isso: :dateExample.',
    'delimiter_help'         => 'Escolha o delimitador de campo que é usado em seu arquivo de entrada. Se não tiver certeza, a vírgula é a opção mais segura.',
    'import_account_help'    => 'Se o seu arquivo CSV NÃO contém informações sobre sua(s) conta(s) ativa(s), use este combobox para selecionar para qual conta pertencem as transações no CSV.',
    'upload_not_writeable'   => 'A caixa cinza contém um caminho para um arquivo. Deve ser possível escrever nele. Por favor, certifique-se de que é.',

    // roles
    'column_roles_title'     => 'Definir as funções da coluna',
    'column_roles_table'     => 'Tabela',
    'column_name'            => 'Nome da coluna',
    'column_example'         => 'Dados de exemplo da coluna',
    'column_role'            => 'Significado dos dados da coluna',
    'do_map_value'           => 'Mapear estes valores',
    'column'                 => 'Coluna',
    'no_example_data'        => 'Não há dados de exemplo disponíveis',
    'store_column_roles'     => 'Continuar a importação',
    'do_not_map'             => '(não mapear)',
    'map_title'              => 'Conectar dados importados para dados do Firefly III',
    'map_text'               => 'Nas tabelas a seguir, o valor à esquerda mostra informações encontradas no seu arquivo CSV carregado. É sua tarefa mapear esse valor, se possível, para um valor já presente em seu banco de dados. O Firefly vai se ater a esse mapeamento. Se não há nenhum valor para mapear, ou não quer mapear o valor específico, não selecione nada.',

    'field_value'          => 'Valor do campo',
    'field_mapped_to'      => 'Mapeado para',
    'store_column_mapping' => 'Armazenar mapeamento',

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