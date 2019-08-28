<?php

/**
 * intro.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
    // index
    'index_intro'                                     => 'Bem-vindo à página de inicial do Firefly III. Por favor, aproveite esta introdução para verificar como funciona o Firefly III.',
    'index_accounts-chart'                            => 'Este gráfico mostra o saldo atual de suas contas de ativos. Você pode selecionar as contas visíveis aqui nas suas preferências.',
    'index_box_out_holder'                            => 'Esta pequena caixa e as caixas próximas a esta lhe darão uma rápida visão geral de sua situação financeira.',
    'index_help'                                      => 'Se você precisar de ajuda com uma página ou um formulário, pressione este botão.',
    'index_outro'                                     => 'A maioria das páginas do Firefly III começará com uma pequena turnê como esta. Entre em contato comigo quando tiver dúvidas ou comentários. Vamos lá!',
    'index_sidebar-toggle'                            => 'Para criar novas transações, contas ou outras coisas, use o menu abaixo deste ícone.',
    'index_cash_account'                              => 'Estas são as contas criadas até agora. Você pode usar a conta de caixa para rastrear as despesas de caixa, mas não é obrigatório, claro.',

    // transactions (withdrawal)
    'transactions_create_withdrawal_source'           => 'Selecione sua conta favorita ou passivo deste dropdown.',
    'transactions_create_withdrawal_destination'      => 'Selecione uma conta de despesas aqui. Deixe em branco se você quiser fazer uma despesa em dinheiro.',
    'transactions_create_withdrawal_foreign_currency' => 'Use este campo para definir uma moeda estrangeira e quantia.',
    'transactions_create_withdrawal_more_meta'        => 'Muitos outros metadados que você definiu nesses campos.',
    'transactions_create_withdrawal_split_add'        => 'Se você quiser dividir uma transação, adicione mais divisões com este botão',

    // transactions (deposit)
    'transactions_create_deposit_source'              => 'Selecione ou digite o beneficiário neste/a dropdown/caixa de texto de preenchimento automático. Deixe em branco se você quiser fazer um depósito em dinheiro.',
    'transactions_create_deposit_destination'         => 'Selecione uma conta de ativo ou passivo aqui.',
    'transactions_create_deposit_foreign_currency'    => 'Use este campo para definir uma moeda estrangeira e quantia.',
    'transactions_create_deposit_more_meta'           => 'Muitos outros metadados que você definiu nesses campos.',
    'transactions_create_deposit_split_add'           => 'Se você quiser dividir uma transação, adicione mais divisões com este botão',

    // transactions (transfer)
    'transactions_create_transfer_source'             => 'Selecione a conta do ativo de origem aqui.',
    'transactions_create_transfer_destination'        => 'Selecione a conta do ativo de destino aqui.',
    'transactions_create_transfer_foreign_currency'   => 'Use este campo para definir uma moeda estrangeira e quantia.',
    'transactions_create_transfer_more_meta'          => 'Muitos outros metadados que você definiu nesses campos.',
    'transactions_create_transfer_split_add'          => 'Se você quiser dividir uma transação, adicione mais divisões com este botão',

    // create account:
    'accounts_create_iban'                            => 'Dê a suas contas um IBAN válido. Isso poderá tornar a importação de dados muito fácil no futuro.',
    'accounts_create_asset_opening_balance'           => 'As contas de bens podem ter um "saldo de abertura", indicando o início do histórico desta conta no Firefly III.',
    'accounts_create_asset_currency'                  => 'Firefly III suporta múltiplas moedas. As contas de ativos têm uma moeda principal, que você deve definir aqui.',
    'accounts_create_asset_virtual'                   => 'Às vezes, ajuda a dar à sua conta um saldo virtual: um valor extra sempre adicionado ou removido do saldo real.',

    // budgets index
    'budgets_index_intro'                             => 'Os orçamentos são usados ​​para gerenciar suas finanças e formar uma das principais funções do Firefly III.',
    'budgets_index_set_budget'                        => 'Defina seu orçamento total para todos os períodos, de modo que o Firefly III possa lhe dizer se você orçou todo o dinheiro disponível.',
    'budgets_index_see_expenses_bar'                  => 'Gastar dinheiro vai preencher lentamente esta barra.',
    'budgets_index_navigate_periods'                  => 'Navegue por períodos para definir os orçamentos facilmente antes do tempo.',
    'budgets_index_new_budget'                        => 'Crie novos orçamentos conforme for entendendo o programa.',
    'budgets_index_list_of_budgets'                   => 'Use esta tabela para definir os montantes para cada orçamento e veja como você está fazendo.',
    'budgets_index_outro'                             => 'Para saber mais sobre orçamentação, clique no ícone de ajuda no canto superior direito.',

    // reports (index)
    'reports_index_intro'                             => 'Use esses relatórios para obter informações detalhadas sobre suas finanças.',
    'reports_index_inputReportType'                   => 'Escolha um tipo de relatório. Confira as páginas de ajuda para ver o que cada relatório mostra.',
    'reports_index_inputAccountsSelect'               => 'Você pode excluir ou incluir contas de ativos de acordo com a sua demanda.',
    'reports_index_inputDateRange'                    => 'O intervalo de datas selecionado depende inteiramente de você: de um dia a 10 anos.',
    'reports_index_extra-options-box'                 => 'Dependendo do relatório que você selecionou, você pode usar filtros e opções adicionais aqui. Observe esta caixa quando você altera os tipos de relatórios.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Este relatório lhe dará uma visão geral rápida e abrangente de suas finanças. Se você deseja ver mais alguma coisa, não hesite em contactar-me!',
    'reports_report_audit_intro'                      => 'Este relatório fornecerá informações detalhadas sobre suas contas de ativos.',
    'reports_report_audit_optionsBox'                 => 'Use essas caixas de seleção para mostrar ou ocultar as colunas em que você está interessado.',

    'reports_report_category_intro'                  => 'Este relatório lhe dará uma visão em uma ou várias categorias.',
    'reports_report_category_pieCharts'              => 'Esses gráficos fornecerão informações sobre despesas e receitas por categoria ou por conta.',
    'reports_report_category_incomeAndExpensesChart' => 'Este gráfico mostra suas despesas e receitas por categoria.',

    'reports_report_tag_intro'                  => 'Este relatório lhe dará uma visão de um ou vários indexadores.',
    'reports_report_tag_pieCharts'              => 'Esses gráficos fornecerão informações sobre despesas e receitas por indexador, conta, categoria ou orçamento.',
    'reports_report_tag_incomeAndExpensesChart' => 'Este gráfico mostra suas despesas e receita por indexador.',

    'reports_report_budget_intro'                             => 'Este relatório lhe dará uma visão em um ou vários orçamentos.',
    'reports_report_budget_pieCharts'                         => 'Esses gráficos fornecerão informações sobre despesas por orçamento ou por conta.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Este gráfico mostra suas despesas por orçamento.',

    // create transaction
    'transactions_create_switch_box'                          => 'Use esses botões para mudar rapidamente o tipo de transação que deseja salvar.',
    'transactions_create_ffInput_category'                    => 'Você pode digitar livremente neste campo. As categorias criadas anteriormente serão sugeridas.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Vincule sua retirada a um orçamento para um melhor controle financeiro.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Use este menu quando seu depósito estiver em outra moeda.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Use este menu suspenso quando seu depósito estiver em outra moeda.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Selecione um banco e vincule essa transferência às suas economias.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Este campo mostra o quanto você salvou em cada banco.',
    'piggy-banks_index_button'                                => 'Ao lado desta barra de progresso estão dois botões (+ e -) para adicionar ou remover dinheiro de cada banco.',
    'piggy-banks_index_accountStatus'                         => 'Para cada conta de ativos com pelo menos um banco, o status está listado nesta tabela.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Qual é o teu objetivo? Um novo sofá, uma câmera, dinheiro para emergências?',
    'piggy-banks_create_date'                                 => 'Você pode definir uma data-alvo ou um prazo para seu banco.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Este gráfico mostrará o histórico desse banco.',
    'piggy-banks_show_piggyDetails'                           => 'Alguns detalhes sobre o seu banco',
    'piggy-banks_show_piggyEvents'                            => 'Todas as adições ou remoções também estão listadas aqui.',

    // bill index
    'bills_index_rules'                                       => 'Aqui você visualiza as regras que verificam se esta fatura é afetada',
    'bills_index_paid_in_period'                              => 'Este campo indica quando a conta foi paga pela última vez.',
    'bills_index_expected_in_period'                          => 'Este campo indica, para cada conta, se e quando a próxima fatura é esperada para cair em conta.',

    // show bill
    'bills_show_billInfo'                                     => 'Esta tabela mostra algumas informações gerais sobre este projeto.',
    'bills_show_billButtons'                                  => 'Use este botão para digitalizar novamente transações velhas, então eles serão combinados a este projeto.',
    'bills_show_billChart'                                    => 'Este gráfico mostra as operações vinculadas a este projeto.',

    // create bill
    'bills_create_intro'                                      => 'Use as faturas para acompanhar a quantidade de dinheiro devido por período. Pense em gastos como aluguel, seguro ou pagamentos de hipoteca.',
    'bills_create_name'                                       => 'Use um nome descritivo como "Aluguel" ou "Seguro de saúde".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Selecione um valor mínimo e máximo para esta conta.',
    'bills_create_repeat_freq_holder'                         => 'A maioria das contas são repetidas mensalmente, como no caso de pagamentos fixos mensais. Mas você pode definir outra frequência neste menu aqui.',
    'bills_create_skip_holder'                                => 'Se uma fatura se repete a cada 2 semanas, o campo "ignorar" deve ser definido como "1" para a repetição quinzenal.',

    // rules index
    'rules_index_intro'                                       => 'O Firefly III permite que você gerencie as regras, que serão automaticamente aplicadas a qualquer transação que você crie ou edite.',
    'rules_index_new_rule_group'                              => 'Você pode criar grupos de regras para facilitar o gerenciamento.',
    'rules_index_new_rule'                                    => 'Crie quantas regras desejar.',
    'rules_index_prio_buttons'                                => 'Você pode ordená-los da maneira que desejar.',
    'rules_index_test_buttons'                                => 'Você pode testar suas regras ou aplicá-las em transações existentes.',
    'rules_index_rule-triggers'                               => 'As regras têm "gatilhos/executores/disparadores" e "ações" que você pode encomendar apenas com funções de arrastar e soltar.',
    'rules_index_outro'                                       => 'Certifique-se de verificar as páginas de ajuda usando o ícone (?) No canto superior direito!',

    // create rule:
    'rules_create_mandatory'                                  => 'Escolha um título descritivo e configure quando a regra deve ser executada.',
    'rules_create_ruletriggerholder'                          => 'Adicione tantos disparadores quanto quiser, mas lembre-se de que TODOS os gatilhos devem ter correspondência antes que qualquer ação seja executada.',
    'rules_create_test_rule_triggers'                         => 'Use este botão para ver quais transações combinariam com sua regra.',
    'rules_create_actions'                                    => 'Defina todas ações que desejar realizar.',

    // preferences
    'preferences_index_tabs'                                  => 'Mais opções estão disponíveis atrás dessas abas/guias.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III suporta múltiplas moedas, que você pode alterar nesta página.',
    'currencies_index_default'                                => 'Firefly III tem uma moeda padrão.',
    'currencies_index_buttons'                                => 'Use os botões para mudar a moeda padrão ou habilitar outras moedas.',

    // create currency
    'currencies_create_code'                                  => 'Este código deve ser compatível com ISO (Google é para sua nova moeda).',
];
