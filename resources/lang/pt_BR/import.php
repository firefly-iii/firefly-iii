<?php
/**
 * import.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

return [
    // status of import:
    'status_wait_title'                    => 'Por favor espere...',
    'status_wait_text'                     => 'Esta caixa desaparecerá em um instante.',
    'status_fatal_title'                   => 'Ocorreu um erro fatal',
    'status_fatal_text'                    => 'Ocorreu um erro fatal, cuja rotina de importação não pode se recuperar. Veja a explicação em vermelho abaixo.',
    'status_fatal_more'                    => 'Se o erro for um tempo limite, a importação terá parado a meio caminho. Para algumas configurações do servidor, é apenas o servidor que parou enquanto a importação continua em execução em segundo plano. Para verificar isso, confira os arquivos de log. Se o problema persistir, considere importação na linha de comando em vez disso.',
    'status_ready_title'                   => 'A importação está pronta para começar',
    'status_ready_text'                    => 'A importação está pronta para começar. Toda a configuração que você precisava fazer foi feita. Faça o download do arquivo de configuração. Isso irá ajudá-lo com a importação se não for como planejado. Para realmente executar a importação, você pode executar o seguinte comando no seu console ou executar a importação baseada na web. Dependendo da sua configuração, a importação do console lhe dará mais comentários.',
    'status_ready_noconfig_text'           => 'A importação está pronta para começar. Toda a configuração que você precisava fazer foi feita. Para realmente executar a importação, você pode executar o seguinte comando no seu console ou executar a importação baseada na web. Dependendo da sua configuração, a importação do console lhe dará mais comentários.',
    'status_ready_config'                  => 'Download da configuração',
    'status_ready_start'                   => 'Iniciar a importação',
    'status_ready_share'                   => 'Por favor, considere baixar sua configuração e compartilhá-la no <strong><a href="https://github.com/firefly-iii/import-configurations/wiki">centro de configuração de importação</a></strong>. Isso permitirá que outros usuários do Firefly III importem seus arquivos mais facilmente.',
    'status_job_new'                       => 'O trabalho é novo.',
    'status_job_configuring'               => 'A importação está sendo configurada.',
    'status_job_configured'                => 'A importação está configurada.',
    'status_job_running'                   => 'A importação está em execução.. Aguarde..',
    'status_job_error'                     => 'O trabalho gerou um erro.',
    'status_job_finished'                  => 'A importação terminou!',
    'status_running_title'                 => 'A importação está em execução',
    'status_running_placeholder'           => 'Por favor, aguarde uma atualização...',
    'status_finished_title'                => 'Rotina de importação concluída',
    'status_finished_text'                 => 'A rotina de importação importou seus dados.',
    'status_errors_title'                  => 'Erros durante a importação',
    'status_errors_single'                 => 'Ocorreu um erro durante a importação. Não parece ser fatal.',
    'status_errors_multi'                  => 'Alguns erros ocorreram durante a importação. Estes não parecem ser fatais.',
    'status_bread_crumb'                   => 'Status de importação',
    'status_sub_title'                     => 'Status de importação',
    'config_sub_title'                     => 'Configurar a sua importação',
    'status_finished_job'                  => 'As transações de :count importadas podem ser encontradas na tag <a href=":link" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a>.',
    'status_finished_no_tag'               => 'O Firefly III não coletou nenhum periódico do seu arquivo de importação.',
    'import_with_key'                      => 'Importar com a chave \':key\'',

    // file, upload something
    'file_upload_title'                    => 'Configuração de importação (1/4) - Carregar seu arquivo',
    'file_upload_text'                     => 'Esta rotina irá ajudá-lo a importar arquivos do seu banco para o Firefly III. Por favor, confira as páginas de ajuda no canto superior direito.',
    'file_upload_fields'                   => 'Campos',
    'file_upload_help'                     => 'Selecione seu arquivo',
    'file_upload_config_help'              => 'Se você já importou dados no Firefly III, você pode ter um arquivo de configuração, que irá predefinir os valores de configuração para você. Para alguns bancos, outros usuários forneceram gentilmente o arquivo <a href="https://github.com/firefly-iii/import-configurations/wiki">arquivo de configuração</a>',
    'file_upload_type_help'                => 'Selecione o tipo de arquivo que você fará o upload',
    'file_upload_submit'                   => 'Upload de arquivos',

    // file, upload types
    'import_file_type_csv'                 => 'CSV (valores separados por vírgula)',

    // file, initial config for CSV
    'csv_initial_title'                    => 'Configuração de importação (2/4) - Configuração de importação CSV básica',
    'csv_initial_text'                     => 'Para ser capaz de importar o arquivo corretamente, por favor valide as opções abaixo.',
    'csv_initial_box'                      => 'Configuração básica de importação CSV',
    'csv_initial_box_title'                => 'Opções básicas de configuração de importação CSV',
    'csv_initial_header_help'              => 'Marque esta caixa se a primeira linha do seu arquivo CSV for os títulos das colunas.',
    'csv_initial_date_help'                => 'Formato de data e hora em seu CSV. Siga o formato como indica <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">esta página</a>. O valor padrão analisará datas que se parecem com isso: :dateExample.',
    'csv_initial_delimiter_help'           => 'Escolha o delimitador de campo que é usado em seu arquivo de entrada. Se não tiver certeza, a vírgula é a opção mais segura.',
    'csv_initial_import_account_help'      => 'Se o seu arquivo CSV NÃO contém informações sobre sua(s) conta(s) ativa(s), use este combobox para selecionar para qual conta pertencem as transações no CSV.',
    'csv_initial_submit'                   => 'Continue com o passo 3/4',

    // file, new options:
    'file_apply_rules_title'               => 'Aplicar regras',
    'file_apply_rules_description'         => 'Aplique suas regras. Observe que isso reduz significativamente a velocidade da importação.',
    'file_match_bills_title'               => 'Correspondência de contas',
    'file_match_bills_description'         => 'Combine suas contas para retiradas recém-criadas. Observe que isso diminui significativamente a velocidade de importação.',

    // file, roles config
    'csv_roles_title'                      => 'Configuração de importação (3/4) - Definir o papel de cada coluna',
    'csv_roles_text'                       => 'Cada coluna no seu arquivo CSV contém certos dados. Por favor, indique que tipo de dados, o importador deve esperar. A opção "mapear" dados significa que você vai ligar cada entrada encontrada na coluna para um valor em seu banco de dados. Uma coluna mapeada muitas vezes é a coluna que contém o IBAN da conta oposta. Isso pode ser facilmente combinado para o IBAN já presente em seu banco de dados.',
    'csv_roles_table'                      => 'Tabela',
    'csv_roles_column_name'                => 'Nome da coluna',
    'csv_roles_column_example'             => 'Dados de exemplo da coluna',
    'csv_roles_column_role'                => 'Significado dos dados da coluna',
    'csv_roles_do_map_value'               => 'Mapear estes valores',
    'csv_roles_column'                     => 'Coluna',
    'csv_roles_no_example_data'            => 'Não há dados de exemplo disponíveis',
    'csv_roles_submit'                     => 'Continue com o passo 4/4',

    // not csv, but normal warning
    'roles_warning'                        => 'No mínimo, marque uma coluna como a coluna de quantidade. É aconselhável também selecionar uma coluna para a descrição, data e a conta oposta.',
    'foreign_amount_warning'               => 'Se você marcar uma coluna como contendo um valor em uma moeda estrangeira, você também deve definir a coluna que contém qual moeda é.',
    // file, map data
    'file_map_title'                       => 'Configuração de importação (4/4) - Conecte dados de importação aos dados do Firefly III',
    'file_map_text'                        => 'Nas tabelas a seguir, o valor à esquerda mostra informações encontradas no seu arquivo carregado. É sua tarefa mapear esse valor, se possível, para um valor já presente em seu banco de dados. O Firefly vai se ater a esse mapeamento. Se não há nenhum valor para mapear, ou não quer mapear o valor específico, não selecione nada.',
    'file_map_field_value'                 => 'Valor do campo',
    'file_map_field_mapped_to'             => 'Mapeado para',
    'map_do_not_map'                       => '(não mapear)',
    'file_map_submit'                      => 'Iniciar a importação',
    'file_nothing_to_map'                  => 'Não há dados presentes no seu arquivo que você possa mapear para os valores existentes. Pressione "Iniciar a importação" para continuar.',

    // map things.
    'column__ignore'                       => '(ignorar esta coluna)',
    'column_account-iban'                  => 'Conta Ativa (IBAN)',
    'column_account-id'                    => 'ID da conta ativa (correspondência FF3)',
    'column_account-name'                  => 'Conta Ativa (nome)',
    'column_amount'                        => 'Montante',
    'column_amount_foreign'                => 'Montante (em moeda estrangeira)',
    'column_amount_debit'                  => 'Montante (coluna de débito)',
    'column_amount_credit'                 => 'Montante (coluna de crédito)',
    'column_amount-comma-separated'        => 'Montante (vírgula como separador decimal)',
    'column_bill-id'                       => 'ID da fatura (correspondente FF3)',
    'column_bill-name'                     => 'Nome da Fatura',
    'column_budget-id'                     => 'ID do Orçamento (correspondente FF3)',
    'column_budget-name'                   => 'Nome do Orçamento',
    'column_category-id'                   => 'ID da Categoria (correspondente FF3)',
    'column_category-name'                 => 'Nome da Categoria',
    'column_currency-code'                 => 'Código da Moeda (ISO 4217)',
    'column_foreign-currency-code'         => 'Código de moeda estrangeira (ISO 4217)',
    'column_currency-id'                   => 'ID da Moeda (correspondente FF3)',
    'column_currency-name'                 => 'Nome da Moeda (correspondente FF3)',
    'column_currency-symbol'               => 'Símbolo da Moeda (correspondente FF3)',
    'column_date-interest'                 => 'Data de cálculo de juros',
    'column_date-book'                     => 'Data da reserva de transação',
    'column_date-process'                  => 'Data do processo de transação',
    'column_date-transaction'              => 'Data',
    'column_description'                   => 'Descrição',
    'column_opposing-iban'                 => 'Conta contrária (IBAN)',
    'column_opposing-id'                   => 'ID da Conta Cotrária (correspondente FF3)',
    'column_external-id'                   => 'ID Externo',
    'column_opposing-name'                 => 'Conta contrária (nome)',
    'column_rabo-debit-credit'             => 'Indicador de débito/crédito específico do Rabobank',
    'column_ing-debit-credit'              => 'Indicador de débito/crédito específico do ING',
    'column_sepa-ct-id'                    => 'ID da Transferência de Crédito fim-a-fim SEPA',
    'column_sepa-ct-op'                    => 'Transferência de Crédito SEPA conta contrária',
    'column_sepa-db'                       => 'Débito direto SEPA',
    'column_tags-comma'                    => 'Tags (separadas por vírgula)',
    'column_tags-space'                    => 'Tags (separadas por espaço)',
    'column_account-number'                => 'Conta ativa (número da conta)',
    'column_opposing-number'               => 'Conta Contrária (número da conta)',
    'column_note'                          => 'Nota(s)',

    // prerequisites
    'prerequisites'                        => 'Pré-requisitos',

    // bunq
    'bunq_prerequisites_title'             => 'Pré-requisitos para uma importação de bunq',
    'bunq_prerequisites_text'              => 'Para importar a partir de bunq, você precisa obter uma chave API. Você pode fazer isso através do aplicativo.',

    // Spectre
    'spectre_title'                        => 'Importar usando Spectre',
    'spectre_prerequisites_title'          => 'Pré-requisitos para uma importação usando Spectre',
    'spectre_prerequisites_text'           => 'Para importar dados usando a Spectre API, você deve fornecer ao Firefly III dois valores secretos. Eles podem ser encontrados na <a href="https://www.saltedge.com/clients/profile/secrets">página de segredos</a>.',
    'spectre_enter_pub_key'                => 'A importação só funcionará quando você inserir essa chave pública em sua <a href="https://www.saltedge.com/clients/security/edit">página de segurança</a>.',
    'spectre_accounts_title'               => 'Selecione as contas a serem importadas',
    'spectre_accounts_text'                => 'Cada conta à esquerda abaixo foi encontrada pela Spectre e pode ser importada para Firefly III. Por favor selecione a conta ativa que deve armazenar as transações. Se você não deseja importar de qualquer conta específica, desmarque a caixa de seleção.',
    'spectre_do_import'                    => 'Sim, importe a partir desta conta',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Status',
    'spectre_extra_key_card_type'          => 'Tipo de Cartão',
    'spectre_extra_key_account_name'       => 'Nome da Conta',
    'spectre_extra_key_client_name'        => 'Nome do cliente',
    'spectre_extra_key_account_number'     => 'Número da conta',
    'spectre_extra_key_blocked_amount'     => 'Montante bloqueado',
    'spectre_extra_key_available_amount'   => 'Montante disponível',
    'spectre_extra_key_credit_limit'       => 'Limite de crédito',
    'spectre_extra_key_interest_rate'      => 'Taxa de juros',
    'spectre_extra_key_expiry_date'        => 'Data de vencimento',
    'spectre_extra_key_open_date'          => 'Data de abertura',
    'spectre_extra_key_current_time'       => 'Hora atual',
    'spectre_extra_key_current_date'       => 'Data atual',
    'spectre_extra_key_cards'              => 'Cartões',
    'spectre_extra_key_units'              => 'Unidades',
    'spectre_extra_key_unit_price'         => 'Preço unitário',
    'spectre_extra_key_transactions_count' => 'Contagem de transações',

    // various other strings:
    'imported_from_account'                => 'Importado de ":account"',
];

