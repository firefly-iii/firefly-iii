<?php

/**
 * demo.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
    'no_demo_text'           => 'Desculpe, não há nenhum texto extra de explicação para <abbr title=":route">esta página</abbr>.',
    'see_help_icon'          => 'No entanto, o <i class="fa fa-question-circle"></i>-ícone no canto superior direito pode lhe dizer mais.',
    'index'                  => 'Bem-vindo ao <strong>Firefly III</strong>! Nesta página você pode obter uma rápida visão geral de suas finanças. Para mais informações, confira Contas de Ativos &rarr; <a href=":asset">Contas de Ativos</a> e, claro, as páginas de <a href=":budgets">Orçamentos</a> e <a href=":reports">Relatório</a>.Ou então, dê uma olhada ao redor e veja onde você vai parar.',
    'accounts-index'         => 'Contas de ativo são suas contas bancárias pessoais. Contas de despesa são aquilo em que você gasta seu dinheiro, tais como lojas e amigos. Contas de receitas são as fontes das quais você recebe seu dinheiro, como o seu trabalho, o governo ou outras fontes de renda. Passivos são suas dívidas e empréstimos, como dívidas antigas de cartão de crédito ou empréstimos estudantis. Nesta página você pode editá-los ou removê-los.',
    'budgets-index'          => 'Esta página mostra a você uma visão geral dos seus orçamentos. A barra superior mostra a quantidade disponível a ser orçamentada. Isto pode ser personalizado para qualquer valor clicando o montante à direita. A quantidade que você gastou de fato é mostrada na barra abaixo. Abaixo, estão as despesas para cada orçamento e o que você orçou neles.',
    'reports-index-start'    => 'Firefly III suporta vários tipos de relatórios. Leia sobre eles clicando no<i class="fa fa-question-circle"></i>-ícone no canto superior direito.',
    'reports-index-examples' => 'Certifique-se de verificar estes exemplos: <a href=":one">um quadro financeiro mensal</a>, <a href=":two">um quadro financeiro anual</a> e <a href=":three">uma visão geral orçamentária</a>.',
    'currencies-index'       => 'Firefly III oferece suporte a várias moedas. Embora o padrão seja o Euro, ela pode ser definida para o dólar americano e muitas outras moedas. Como você pode ver uma pequena seleção de moedas foi incluída, mas você pode adicionar suas próprias se desejar. No entanto, alterar a moeda padrão não vai mudar a moeda de transações existentes: Firefly III suporta o uso de várias moedas ao mesmo tempo.',
    'transactions-index'     => 'Estas despesas, depósitos e transferências não são fantasiosas. Elas foram geradas automaticamente.',
    'piggy-banks-index'      => 'Como você pode ver, existem três cofrinhos. Use o sinal de mais e menos botões para influenciar a quantidade de dinheiro em cada cofrinho. Clique no nome do cofrinho para ver a administração de cada cofrinho.',
    'import-index'           => 'Qualquer arquivo CSV pode ser importado para o Firefly III. Importações de dados de bunq e Specter também são suportadas. Outros bancos e agregadores financeiros serão implementados futuramente. Como usuário de demonstração, no entanto, você só pode ver o provedor "falso" em ação. Ele irá gerar transações aleatórias para lhe mostrar como funciona o processo.',
    'profile-index'          => 'Tenha em mente que o site de demonstração reinicia a cada 4 horas. Seu acesso pode ser revogado a qualquer momento. Isso acontece automaticamente e não é um erro.',
];
