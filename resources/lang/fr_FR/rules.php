<?php

/*
 * rules.php
 * Copyright (c) 2023 james@firefly-iii.org
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
    'main_message'                                => 'L\'action ":action", présente dans la règle ":rule", n\'a pas pu être appliquée à l\'opération #:group : :error',
    'find_or_create_tag_failed'                   => 'Impossible de trouver ou de créer le tag ":tag"',
    'tag_already_added'                           => 'L\'étiquette ":tag" est déjà liée à cette opération',
    'inspect_transaction'                         => 'Inspecter l\'opération ":title" @ Firefly III',
    'inspect_rule'                                => 'Inspecter la règle ":title" @ Firefly III',
    'journal_other_user'                          => 'Cette opération n\'appartient pas à l\'utilisateur',
    'no_such_journal'                             => 'Cette opération n\'existe pas',
    'journal_already_no_budget'                   => 'Cette opération n\'a pas de budget, elle ne peut donc pas être supprimée',
    'journal_already_no_category'                 => 'Cette opération n\'a pas de catégorie, elle ne peut donc pas être supprimée',
    'journal_already_no_notes'                    => 'Cette opération n\'a pas de note, elle ne peut donc pas être supprimée',
    'journal_not_found'                           => 'Firefly III ne trouve pas l\'opération demandée',
    'split_group'                                 => 'Firefly III ne peut pas exécuter cette action sur une opération avec une ventilation multiple',
    'is_already_withdrawal'                       => 'Cette opération est déjà une dépense',
    'is_already_deposit'                          => 'Cette opération est déjà un dépôt',
    'is_already_transfer'                         => 'Cette opération est déjà un transfert',
    'is_not_transfer'                             => 'Cette opération n\'est pas un transfert',
    'complex_error'                               => 'Quelque chose de compliqué s\'est mal passé. Nous en sommes désolés. Veuillez consulter les journaux de Firefly III',
    'no_valid_opposing'                           => 'La conversion a échoué car il n\'y a pas de compte valide nommé ":account"',
    'new_notes_empty'                             => 'Les notes à définir sont vides',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III ne peut pas convertir un ":type" en retrait',
    'unsupported_transaction_type_deposit'        => 'Firefly III ne peut pas convertir un ":type" en dépôt',
    'unsupported_transaction_type_transfer'       => 'Firefly III ne peut pas convertir un ":type" en transfert',
    'already_has_source_asset'                    => 'Cette opération a déjà «:name» comme compte d\'actif source',
    'already_has_destination_asset'               => 'Cette opération a déjà «:name» comme compte d\'actif de destination',
    'already_has_destination'                     => 'Cette opération a déjà «:name» comme compte de destination',
    'already_has_source'                          => 'Cette opération a déjà ":name" comme compte source',
    'already_linked_to_subscription'              => 'L\'opération est déjà liée à l\'abonnement ":name"',
    'already_linked_to_category'                  => 'L\'opération est déjà liée à la catégorie ":name"',
    'already_linked_to_budget'                    => 'L\'opération est déjà liée au budget ":name"',
    'cannot_find_subscription'                    => 'Firefly III ne trouve pas l\'abonnement ":name"',
    'no_notes_to_move'                            => 'L\'opération n\'a pas de notes à déplacer dans le champ description',
    'no_tags_to_remove'                           => 'L\'opération n\'a pas de tags à supprimer',
    'not_withdrawal'                              => 'L\'opération n\'est pas une dépense',
    'not_deposit'                                 => 'L\'opération n\'est pas un dépôt',
    'cannot_find_tag'                             => 'Firefly III ne trouve pas le tag ":tag"',
    'cannot_find_asset'                           => 'Firefly III ne trouve pas le compte d\'actif ":name"',
    'cannot_find_accounts'                        => 'Firefly III ne trouve pas le compte source ou le compte de destination',
    'cannot_find_source_transaction'              => 'Firefly III ne trouve pas l\'opération source',
    'cannot_find_destination_transaction'         => 'Firefly III ne trouve pas l\'opération de destination',
    'cannot_find_source_transaction_account'      => 'Firefly III ne trouve pas le compte d\'opérations source',
    'cannot_find_destination_transaction_account' => 'Firefly III ne trouve pas le compte d\'opérations de destination',
    'cannot_find_piggy'                           => 'Firefly III ne trouve pas de tirelire nommée ":name"',
    'no_link_piggy'                               => 'Les comptes de cette opération ne sont pas liés à la tirelire, aucune action ne sera donc entreprise',
    'cannot_unlink_tag'                           => 'L\'étiquette ":tag" n\'est pas liée à cette opération',
    'cannot_find_budget'                          => 'Firefly III ne trouve pas le budget ":name"',
    'cannot_find_category'                        => 'Firefly III ne trouve pas la catégorie ":name"',
    'cannot_set_budget'                           => 'Firefly III ne peut pas attribuer le budget ":name" à une opération de type ":type"',
];
