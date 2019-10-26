<?php
/**
 * api.php
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
    'error_no_upload'                    => 'No file has been uploaded for this attachment (yet).',
    'error_file_lost'                    => 'Could not find the indicated attachment. The file is no longer there.',
    'error_store_bill'                   => 'Could not store new bill.',
    'error_store_budget'                 => 'Could not store new budget.',
    'error_unknown_budget'               => 'Unknown budget.',
    'error_store_new_category'           => 'Could not store new category.',
    'error_no_access'                    => 'No access to method.',
    'error_no_access_ownership'          => 'No access to method, user is not owner.',
    'error_no_access_currency_in_use'    => 'No access to method, currency is in use.',
    'error_store_new_currency'           => 'Could not store new currency.',
    'error_unknown_source_currency'      => 'Unknown source currency.',
    'error_unknown_destination_currency' => 'Unknown destination currency.',
    'error_delete_link_type'             => 'You cannot delete this link type (:id, :name)',
    'error_edit_link_type'               => 'You cannot edit this link type (:id, :name)',
    'error_owner_role_needed'            => 'You need the "owner"-role to do this.',
    'error_store_new_piggybank'          => 'Could not store new piggy bank.',
    'error_fire_cronjob'                 => 'Could not fire recurring cron job.',
    'error_no_rules_in_rule_group'       => 'No rules in this rule group.',
    'error_source_or_dest_null'          => 'Source or destination is NULL.'

];