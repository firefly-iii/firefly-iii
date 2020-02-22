<?php

/**
 * demo.php
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
    'no_demo_text'           => 'Désolé, il n’y a aucune explication supplémentaire propre à la démonstration pour <abbr title=":route">cette page</abbr>.',
    'see_help_icon'          => 'Cependant, l\'icône <i class="fa fa-question-circle"></i> située dans le coin supérieur droit peut vous en dire plus.',
    'index'                  => 'Bienvenue chez <strong>Firefly III</strong> ! Vous avez sur cette page un aperçu rapide de vos finances. Pour plus d’informations, consultez vos &rarr; <a href=":asset">Comptes d’actifs</a> et, bien sûr, les pages des <a href=":budgets">budgets</a> et des <a href=":reports">rapports</a>. Ou jetez simplement un coup d’œil et voyez où vous en êtes.',
    'accounts-index'         => 'Les comptes d’actifs sont vos comptes bancaires personnels. Les comptes de dépenses sont des comptes où vous dépensez de l’argent, comme les magasins et les amis. Les comptes de recettes sont des comptes où vous recevez de l’argent, comme votre travail, le gouvernement ou d’autres sources de revenu. Les passifs sont vos dettes et crédits tel que vos crédits à la consommation ou vos prêts étudiant. Sur cette page, vous pouvez les modifier ou les supprimer.',
    'budgets-index'          => 'Cette page vous présente un aperçu de vos budgets. La barre du haut affiche le montant disponible à budgétiser. Il est possible de le personnaliser pour n\'importe quelle période en cliquant sur le montant sur la droite. Le montant que vous avez réellement dépensé s’affiche dans la barre en dessous. Plus bas se trouve les dépenses par budget et le prévisionnel de chacun de ces budgets.',
    'reports-index-start'    => 'Firefly III prend en charge un certain nombre de types de rapports. Lisez-les en cliquant sur l\'icône <i class="fa fa-question-circle"></i> dans le coin supérieur droit.',
    'reports-index-examples' => 'N’oubliez pas de consultez ces exemples : <a href=":one">un aperçu financier mensuel</a>, <a href=":two">une vue d’ensemble financière annuelle</a> ainsi <a href=":three">qu’une présentation du budget</a>.',
    'currencies-index'       => 'Firefly III supporte de multiples devises. Bien que l\'Euro soit la devise par défaut, il est possible d\'utiliser le Dollar américain et de nombreuses autres devises. Comme vous pouvez le voir, une petite sélection de devises existe déjà mais vous pouvez ajouter vos propres devises si vous le souhaitez. Gardez à l\'esprit que la modification de la devise par défaut ne modifie pas la devise des transactions existantes : Firefly III prend en charge l’utilisation de plusieurs devises en même temps.',
    'transactions-index'     => 'Ces dépenses, dépôts et transferts ne sont pas particulièrement imaginatifs. Ils ont été générés automatiquement.',
    'piggy-banks-index'      => 'Comme vous pouvez le voir, il y a trois tirelires. Utilisez les boutons plus et moins pour influer sur le montant d’argent dans chaque tirelire. Cliquez sur le nom de la tirelire pour voir l’administration pour chaque tirelire.',
    'import-index'           => 'Tout fichier CSV peut être importé dans Firefly III. L\'importation de données depuis bunq et Spectre est également supportée. D\'autres banques et agrégateurs financiers seront mis en place dans le futur. En tant qu\'utilisateur du site de démonstration, vous ne pouvez voir que le «faux» service en action. Il va générer des transactions aléatoires pour vous montrer comment se déroule le processus.',
    'profile-index'          => 'Garder à l’esprit que le site de démo se réinitialise toutes les quatre heures. Votre accès peut être révoqué à n\'importe quel moment. Ceci intervient automatiquement, ce n\'est pas un bug.',
];
