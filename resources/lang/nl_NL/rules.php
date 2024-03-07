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
    'main_message'                                => 'Actie ":action", aanwezig in regel ":rule", kan niet worden toegepast op transactie #:group: :error',
    'find_or_create_tag_failed'                   => 'Kan de tag ":tag" niet vinden of aanmaken',
    'tag_already_added'                           => 'Tag ":tag" is al gekoppeld aan deze transactie',
    'inspect_transaction'                         => 'Bekijk transactie ":title" @ Firefly III',
    'inspect_rule'                                => 'Bekijk regel ":title" @ Firefly III',
    'journal_other_user'                          => 'Deze transactie hoort niet bij die gebruiker',
    'no_such_journal'                             => 'Transactie bestaat niet',
    'journal_already_no_budget'                   => 'Deze transactie heeft geen budget, dus deze kan niet worden verwijderd',
    'journal_already_no_category'                 => 'Deze transactie heeft geen categorie, dus deze kan niet worden verwijderd',
    'journal_already_no_notes'                    => 'Deze transactie heeft geen notities, dus deze kunnen niet worden verwijderd',
    'journal_not_found'                           => 'Firefly III kan die transactie niet vinden',
    'split_group'                                 => 'Firefly III kan deze actie niet uitvoeren op een transactie met meerdere splits',
    'is_already_withdrawal'                       => 'Deze transactie is al een uitgave',
    'is_already_deposit'                          => 'Deze transactie is al inkomsten',
    'is_already_transfer'                         => 'Deze transactie is al een overschrijving',
    'is_not_transfer'                             => 'Deze transactie is geen overschrijving',
    'complex_error'                               => 'Er ging iets moeilijks fout, sorry. Check de logs van Firefly III',
    'no_valid_opposing'                           => 'Omzetten mislukt omdat er geen geldige rekening met naam ":account" bestaat',
    'new_notes_empty'                             => 'De notities die moeten worden ingesteld zijn leeg',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III kan geen ":type" converteren naar een uitgave',
    'unsupported_transaction_type_deposit'        => 'Firefly III kan geen ":type" converteren naar inkomsten',
    'unsupported_transaction_type_transfer'       => 'Firefly III kan geen ":type" converteren naar een overschrijving',
    'already_has_source_asset'                    => 'Deze transactie heeft ":name" al als bronrekening',
    'already_has_destination_asset'               => 'Deze transactie heeft ":name" al als doelrekening',
    'already_has_destination'                     => 'Deze transactie heeft ":name" al als doelrekening',
    'already_has_source'                          => 'Deze transactie heeft ":name" al als bronrekening',
    'already_linked_to_subscription'              => 'De transactie is al gekoppeld aan abonnement ":name"',
    'already_linked_to_category'                  => 'De transactie is al gekoppeld aan categorie ":name"',
    'already_linked_to_budget'                    => 'De transactie is al gekoppeld aan budget ":name"',
    'cannot_find_subscription'                    => 'Firefly III kan geen abonnement met naam ":name" vinden',
    'no_notes_to_move'                            => 'De transactie heeft geen notities om te verplaatsen naar het omschrijvingsveld',
    'no_tags_to_remove'                           => 'De transactie heeft geen tags om te verwijderen',
    'not_withdrawal'                              => 'Deze transactie is niet een uitgave',
    'not_deposit'                                 => 'Deze transactie is geen inkomsten',
    'cannot_find_tag'                             => 'Firefly III kan tag ":tag" niet vinden',
    'cannot_find_asset'                           => 'Firefly III kan geen betaalrekening met naam ":name" vinden',
    'cannot_find_accounts'                        => 'Firefly III kan de bron- of doelrekening niet vinden',
    'cannot_find_source_transaction'              => 'Firefly III kan de brontransactie niet vinden',
    'cannot_find_destination_transaction'         => 'Firefly III kan de doeltransactie niet vinden',
    'cannot_find_source_transaction_account'      => 'Firefly III kan de brontransactierekening niet vinden',
    'cannot_find_destination_transaction_account' => 'Firefly III kan de doeltransactierekening niet vinden',
    'cannot_find_piggy'                           => 'Firefly III kan geen spaarpotje vinden met de naam ":name"',
    'no_link_piggy'                               => 'De accounts van deze transactie zijn niet gekoppeld dit spaarpotje',
    'cannot_unlink_tag'                           => 'Tag ":tag" is niet gekoppeld aan deze transactie',
    'cannot_find_budget'                          => 'Firefly III kan budget ":name" niet vinden',
    'cannot_find_category'                        => 'Firefly III kan categorie ":name" niet vinden',
    'cannot_set_budget'                           => 'Firefly III kan budget ":name" niet instellen op een transactie van het type ":type"',
];
