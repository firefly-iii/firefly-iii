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
    'main_message'                                => 'Akcja ":action", obecna w regule ":rule", nie mogła zostać zastosowana do transakcji #:group: :error',
    'find_or_create_tag_failed'                   => 'Nie można znaleźć lub utworzyć tagu ":tag"',
    'tag_already_added'                           => 'Tag ":tag" jest już powiązany z tą transakcją',
    'inspect_transaction'                         => 'Sprawdź transakcję ":title" @ Firefly III',
    'inspect_rule'                                => 'Sprawdź regułę ":title" @ Firefly III',
    'journal_other_user'                          => 'Ta transakcja nie należy do użytkownika',
    'no_such_journal'                             => 'Ta transakcja nie istnieje',
    'journal_already_no_budget'                   => 'Ta transakcja nie ma budżetu, więc nie może zostać usunięta',
    'journal_already_no_category'                 => 'Ta transakcja nie miała kategorii, więc kategoria nie może zostać usunięta',
    'journal_already_no_notes'                    => 'Ta transakcja nie miała notatek, więc te nie mogą zostać usunięte',
    'journal_not_found'                           => 'Firefly III nie może znaleźć żądanej transakcji',
    'split_group'                                 => 'Firefly III nie może wykonać tej akcji na transakcji z wieloma podziałami',
    'is_already_withdrawal'                       => 'Ta transakcja jest już wypłatą',
    'is_already_deposit'                          => 'Ta transakcja jest już wpłatą',
    'is_already_transfer'                         => 'Ta transakcja jest już transferem',
    'is_not_transfer'                             => 'Ta transakcja nie jest transferem',
    'complex_error'                               => 'Coś skomplikowanego poszło nie tak. Proszę sprawdź logi Firefly III',
    'no_valid_opposing'                           => 'Konwersja nie powiodła się, ponieważ nie ma poprawnego konta o nazwie ":account"',
    'new_notes_empty'                             => 'Notatki które mają być ustawione są puste',
    'unsupported_transaction_type_withdrawal'     => 'Firefly III nie może przekonwertować ":type" na wypłatę',
    'unsupported_transaction_type_deposit'        => 'Firefly III nie może przekonwertować ":type" na wpłatę',
    'unsupported_transaction_type_transfer'       => 'Firefly III nie może przekonwertować ":type" na transfer',
    'already_has_source_asset'                    => 'Ta transakcja ma już ustawione ":name" jako konto aktywów',
    'already_has_destination_asset'               => 'Ta transakcja ma już ustawione ":name" jako docelowe konto aktywów',
    'already_has_destination'                     => 'Ta transakcja ma już ustawione ":name" jako konto docelowe',
    'already_has_source'                          => 'Ta transakcja ma już ustawione ":name" jako konto źródłowe',
    'already_linked_to_subscription'              => 'Transakcja jest już powiązana z rachunkiem ":name"',
    'already_linked_to_category'                  => 'Transakcja jest już powiązana z kategorią ":name"',
    'already_linked_to_budget'                    => 'Transakcja jest już powiązana z budżetem ":name"',
    'cannot_find_subscription'                    => 'Firefly III nie może znaleźć rachunku ":name"',
    'no_notes_to_move'                            => 'Transakcja nie ma notatek do przeniesienia do pola opisu',
    'no_tags_to_remove'                           => 'Transakcja nie ma tagów do usunięcia',
    'not_withdrawal'                              => 'Transakcja nie jest wydatkiem',
    'not_deposit'                                 => 'Transakcja nie jest wpłatą',
    'cannot_find_tag'                             => 'Firefly III nie może znaleźć tagu ":tag"',
    'cannot_find_asset'                           => 'Firefly III nie może znaleźć konta aktywów ":name"',
    'cannot_find_accounts'                        => 'Firefly III nie może znaleźć konta źródłowego lub docelowego',
    'cannot_find_source_transaction'              => 'Firefly III nie może znaleźć transakcji źródłowej',
    'cannot_find_destination_transaction'         => 'Firefly III nie może znaleźć docelowej transakcji',
    'cannot_find_source_transaction_account'      => 'Firefly III nie może znaleźć konta źródłowego transakcji',
    'cannot_find_destination_transaction_account' => 'Firefly III nie może znaleźć konta docelowego transakcji',
    'cannot_find_piggy'                           => 'Firefly III nie może znaleźć skarbonki o nazwie ":name"',
    'no_link_piggy'                               => 'Konta tej transakcji nie są powiązane ze skarbonką - więc nie zostaną podjęte żadne działania',
    'cannot_unlink_tag'                           => 'Tag ":tag" nie jest powiązany z tą transakcją',
    'cannot_find_budget'                          => 'Firefly III nie może znaleźć budżetu ":name"',
    'cannot_find_category'                        => 'Firefly III nie może znaleźć kategorii ":name"',
    'cannot_set_budget'                           => 'Firefly III nie może ustawić budżetu ":name" dla transakcji typu ":type"',
];
