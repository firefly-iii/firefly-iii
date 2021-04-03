<?php

/**
 * intro.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    // index
    'index_intro'                                     => 'Bem vindo à pagina inicial do Firefly III. Por favor, reserve um momento para ler a nossa introdução para perceber o modo de funcionamento do Firefly III.',
    'index_accounts-chart'                            => 'Este gráfico mostra o saldo atual das tuas contas de ativas. Podes selecionar as contas a aparecer aqui, nas tuas preferências.',
    'index_box_out_holder'                            => 'Esta caixa e as restantes ao lado dão-lhe um breve resumo da sua situação financeira.',
    'index_help'                                      => 'Se alguma vez precisares de ajuda com uma página ou um formulário, usa este botão.',
    'index_outro'                                     => 'A maioria das paginas no Firefly III vão começar com um pequeno tutorial como este. Por favor contacta-me quando tiveres questões ou comentários. Desfruta!',
    'index_sidebar-toggle'                            => 'Para criar transações, contas ou outras coisas, usa o menu sobre este ícone.',
    'index_cash_account'                              => 'Estas são as contas criadas até agora. Você pode usar uma conta caixa para acompanhar as suas despesas em dinheiro, no entanto não é obrigatório usar.',

    // transactions
    'transactions_create_basic_info'                  => 'Adicione a informação básica da sua transação. Origem, destino, data e descrição.',
    'transactions_create_amount_info'                 => 'Adicione a quantia da transação. Se necessário os campos irão atualizar-se automaticamente de informações de moedas estrangeiras.',
    'transactions_create_optional_info'               => 'Todos estes campos são opcionais. Adicionar meta-dados aqui irá ajudar a organizar melhor as suas transações.',
    'transactions_create_split'                       => 'Se quiser dividir uma transação, adicione mais divisões com este botão',

    // create account:
    'accounts_create_iban'                            => 'Atribua IBAN\'s válidos nas suas contas. Isto pode ajudar a tornar a importação de dados muito simples no futuro.',
    'accounts_create_asset_opening_balance'           => 'Contas de ativos podem ter um saldo de abertura, desta forma indicando o inicio do seu historial no Firefly III.',
    'accounts_create_asset_currency'                  => 'O Firefly III suporta múltiplas moedas. Contas de ativos tem uma moeda principal, que deve ser definida aqui.',
    'accounts_create_asset_virtual'                   => 'Por vezes, pode ajudar dar a tua conta um saldo virtual: uma quantia extra sempre adicionada ou removida do saldo real.',

    // budgets index
    'budgets_index_intro'                             => 'Os orçamentos são usados para gerir as tuas finanças e fazem parte de uma das funções principais do Firefly III.',
    'budgets_index_set_budget'                        => 'Define o teu orçamento total para cada período, assim o Firefly III pode-te dizer se tens orçamentado todo o teu dinheiro disponível.',
    'budgets_index_see_expenses_bar'                  => 'Ao gastar dinheiro esta barra vai sendo preenchida.',
    'budgets_index_navigate_periods'                  => 'Navega através de intervalos para definir os orçamentos antecipadamente.',
    'budgets_index_new_budget'                        => 'Crie novos orçamentos como achar melhor.',
    'budgets_index_list_of_budgets'                   => 'Use esta tabela para definir os valores para cada orçamento e manter o controlo dos gastos.',
    'budgets_index_outro'                             => 'Para obter mais informações sobre orçamentos, verifica o ícone de ajuda no canto superior direito.',

    // reports (index)
    'reports_index_intro'                             => 'Use estes relatórios para obter sínteses detalhadas sobre as suas finanças.',
    'reports_index_inputReportType'                   => 'Escolha um tipo de relatório. Confira as páginas de ajuda para ter a certeza do que cada relatório mostra.',
    'reports_index_inputAccountsSelect'               => 'Podes incluir ou excluir contas de ativos conforme as suas necessidades.',
    'reports_index_inputDateRange'                    => 'O intervalo temporal a definir é totalmente preferencial: desde 1 dia ate 10 anos.',
    'reports_index_extra-options-box'                 => 'Dependendo do relatório que selecionou, pode selecionar campos extra aqui. Repare nesta caixa quando mudar os tipos de relatório.',

    // reports (reports)
    'reports_report_default_intro'                    => 'Este relatório vai-lhe dar uma visão rápida e abrangente das suas finanças. Se desejar ver algo a mais, por favor não hesite em contactar-me!',
    'reports_report_audit_intro'                      => 'Este relatório vai-lhe dar informações detalhadas das suas contas de ativos.',
    'reports_report_audit_optionsBox'                 => 'Usa estes campos para mostrar ou esconder colunas que tenhas interesse.',

    'reports_report_category_intro'                  => 'Este relatório irá-lhe dar informações detalhadas numa ou múltiplas categorias.',
    'reports_report_category_pieCharts'              => 'Estes gráficos irão-lhe dar informações de despesas e receitas, por categoria ou por conta.',
    'reports_report_category_incomeAndExpensesChart' => 'Este gráfico mostra-te as despesas e receitas por categoria.',

    'reports_report_tag_intro'                  => 'Este relatório irá-lhe dar informações de uma ou múltiplas etiquetas.',
    'reports_report_tag_pieCharts'              => 'Estes gráficos irão-lhe dar informações de despesas e receitas por etiqueta, conta, categoria ou orçamento.',
    'reports_report_tag_incomeAndExpensesChart' => 'Este gráfico mostra-lhe as suas despesas e receitas por etiqueta.',

    'reports_report_budget_intro'                             => 'Este relatório irá-lhe dar informações de um ou múltiplos orçamentos.',
    'reports_report_budget_pieCharts'                         => 'Estes gráficos irão-lhe dar informações de despesas por orçamento ou por conta.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Este gráfico mostra-lhe as suas despesas por orçamento.',

    // create transaction
    'transactions_create_switch_box'                          => 'Usa estes botoes para modares rapidamente o tipo de transaccao que desejas gravar.',
    'transactions_create_ffInput_category'                    => 'Podes escrever livremente neste campo. Categorias criadas previamente vao ser sugeridas.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Associa o teu levantamento a um orcamento para um melhor controlo financeiro.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Usa esta caixa de seleccao quando o teu levantamento e noutra divisa.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Utilize esta caixa de seleção quando o seu depósito estiver noutra moeda.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Selecione um mealheiro e associe esta transferência as suas poupanças.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Este campo mostra-te quando ja guardaste em cada mealheiro.',
    'piggy-banks_index_button'                                => 'Ao lado desta barra de progresso existem 2 butoes(+ e -) para adicionares ou removeres dinheiro de cada mealheiro.',
    'piggy-banks_index_accountStatus'                         => 'Para cada conta de activos com pelo menos um mealheiro, o estado e listado nesta tabela.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Qual e o teu objectivo? Um sofa novo, uma camara, dinheiro para emergencias?',
    'piggy-banks_create_date'                                 => 'Podes definir uma data alvo ou um prazo limite para o teu mealheiro.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Este gráfico mostra-lhe o histórico deste mealheiro.',
    'piggy-banks_show_piggyDetails'                           => 'Alguns detalhes sobre o teu mealheiro',
    'piggy-banks_show_piggyEvents'                            => 'Quaisquer adicoes ou remocoes tambem serao listadas aqui.',

    // bill index
    'bills_index_rules'                                       => 'Aqui tu podes ver quais regras serao validadas se esta factura for atingida',
    'bills_index_paid_in_period'                              => 'Este campo indica o ultimo pagamento desta factura.',
    'bills_index_expected_in_period'                          => 'Este campo indica, para cada factura, se, e quando a proxima factura sera atingida.',

    // show bill
    'bills_show_billInfo'                                     => 'Esta tabela mostra alguma informação geral sobre esta fatura.',
    'bills_show_billButtons'                                  => 'Usa este botao para tornar a analizar transaccoes antigas para assim elas poderem ser associadas com esta factura.',
    'bills_show_billChart'                                    => 'Este gráfico mostra as transações associadas a esta fatura.',

    // create bill
    'bills_create_intro'                                      => 'Usa as facturas para controlares o montante de dinheiro e deves em cada periodo. Pensa nas despesas como uma renda, seguro ou pagamentos de hipotecas.',
    'bills_create_name'                                       => 'Usa um nome bem descritivo como "Renda" ou "Seguro de Vida".',
    //'bills_create_match'                                      => 'To match transactions, use terms from those transactions or the expense account involved. All words must match.',
    'bills_create_amount_min_holder'                          => 'Selecciona um montante minimo e maximo para esta factura.',
    'bills_create_repeat_freq_holder'                         => 'A maioria das facturas sao mensais, mas podes definir outra frequencia de repeticao aqui.',
    'bills_create_skip_holder'                                => 'Se uma factura se repete a cada 2 semanas, o campo "pular" deve ser colocado como "1" para saltar toda a semana seguinte.',

    // rules index
    'rules_index_intro'                                       => 'O Firefly III permite-te gerir as regras que vao ser aplicadas automaticamente a qualquer transaccao que crias ou alteras.',
    'rules_index_new_rule_group'                              => 'Podes combinar regras em grupos para uma gestao mais facil.',
    'rules_index_new_rule'                                    => 'Cria quantas regras quiseres.',
    'rules_index_prio_buttons'                                => 'Ordena-as da forma que aches correcta.',
    'rules_index_test_buttons'                                => 'Podes testar as tuas regras ou aplica-las a transaccoes existentes.',
    'rules_index_rule-triggers'                               => 'As regras tem "disparadores" e "accoes" que podes ordenar com drag-and-drop.',
    'rules_index_outro'                                       => 'Certifica-te que olhas a pagina de ajuda no icone (?) no canto superior direito!',

    // create rule:
    'rules_create_mandatory'                                  => 'Escolhe um titulo descriptivo e define quando a regra deve ser disparada.',
    'rules_create_ruletriggerholder'                          => 'Adiciona a quantidade de disparadores que necessites, mas, lembra-te que TODOS os disparadores tem de coincidir antes de qualquer accao ser disparada.',
    'rules_create_test_rule_triggers'                         => 'Usa este botao para ver quais transaccoes podem coincidir com a tua regra.',
    'rules_create_actions'                                    => 'Define todas as accoes que quiseres.',

    // preferences
    'preferences_index_tabs'                                  => 'Mais opções estão disponíveis atrás destas abas.',

    // currencies
    'currencies_index_intro'                                  => 'O Firefly III suporta multiplas divisas que podes mudar nesta pagina.',
    'currencies_index_default'                                => 'O Firefly III tem uma divisa de defeito.',
    'currencies_index_buttons'                                => 'Usa estes botoes para alterar a divisa de defito ou activar outras divisas.',

    // create currency
    'currencies_create_code'                                  => 'Este codigo deve ser compativel com ISO (procura pela tua nova moeda no google).',
];
