/*
 * save-new-link.js
 * Copyright (c) 2026 james@firefly-iii.org
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

export function saveNewLink(e) {
    let index = parseInt(e.currentTarget.dataset.index);

    let linkSelect = document.getElementById('link_type_id_' + index);
    let linkType = linkSelect.value;
    let linkLabel = linkSelect.options[linkSelect.selectedIndex].innerHTML;
    let searchBox = document.getElementById('links_modal_search_' + index);
    let hiddenField = searchBox.parentNode.querySelector('input[name="search"]');

    let linkTypeId = parseInt(linkType.split('_')[0]);
    let linkTypeDirection = linkType.split('_')[1];
    let linkTypeObj = this.formData.linkTypes.find(link => link.id === linkTypeId);

    if ('' === linkType || '' === hiddenField.value || '' === searchBox.value) {
        return;
    }

    // add entry to temporary table.
    console.log('Link ' + linkType + ' ("' + linkLabel + '") to transaction #' + hiddenField.value + '("' + searchBox.value + '")');

    this.links[index].push(
        {
            id: 0,
            link_type: linkTypeId + '_' + linkTypeDirection,
            link_type_id: linkTypeId,
            link_type_direction: linkTypeDirection,
            link_type_label: linkTypeObj[linkTypeDirection],
            journal_id: hiddenField.value,
            group_id: hiddenField.value,
            journal_description: searchBox.value,
            editMode: false,
            stored: false,
        }
    );
    searchBox.value = '';
    hiddenField.value = '';
};
