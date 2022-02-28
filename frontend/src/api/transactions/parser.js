/*
 * parser.js
 * Copyright (c) 2022 james@firefly-iii.org
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

export default class Parser {
  parseResponse(response) {
    let obj = {};
    obj.rows = [];
    obj.rowsPerPage = response.data.meta.pagination.per_page;
    obj.rowsNumber = response.data.meta.pagination.total;

    for (let i in response.data.data) {
      if (response.data.data.hasOwnProperty(i)) {
        let current = response.data.data[i];
        let group = {
          group_id: current.id,
          splits: [],
          group_title: current.attributes.group_title
        };

        for (let ii in current.attributes.transactions) {
          if (current.attributes.transactions.hasOwnProperty(ii)) {
            let transaction = current.attributes.transactions[ii];
            let parsed = {
              group_id: current.id,
              journal_id: parseInt(transaction.transaction_journal_id),
              type: transaction.type,
              description: transaction.description,
              amount: transaction.amount,
              date: transaction.date,
              source: transaction.source_name,
              destination: transaction.destination_name,
              category: transaction.category_name,
              budget: transaction.budget_name,
              currencyCode: transaction.currency_code,
            };
            if (1 === current.attributes.transactions.length && 0 === parseInt(ii)) {
              group.group_title = transaction.description;
            }

            // merge with group if index = 0;
            if (0 === parseInt(ii)) {
              group = {
                ...group,
                ...parsed
              };
            }
            // append to splits if > 1 and > 1
            if (current.attributes.transactions.length > 0) {
              group.splits.push(parsed);
              // add amount:
              if (ii > 0) {
                group.amount = parseFloat(group.amount) + parseFloat(parsed.amount);
              }
            }
          }
        }
        obj.rows.push(group);
      }
    }
    return obj;
  }
}
