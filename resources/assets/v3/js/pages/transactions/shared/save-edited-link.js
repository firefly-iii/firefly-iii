/*
 * save-edited-link.js
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

export function saveEditedLink(e) {
    let rowIndex = parseInt(e.currentTarget.dataset.rowIndex);
    let index = parseInt(e.currentTarget.dataset.index);
    let selector = e.currentTarget.parentNode.querySelector('select[name="new-link-type"]');

    let linkType = selector.value;
    let linkTypeId = parseInt(linkType.split('_')[0]);
    let linkTypeDirection = linkType.split('_')[1];
    let linkTypeObj = this.formData.linkTypes.find(link => link.id === linkTypeId);
    if (typeof linkTypeObj === 'undefined') {
        console.error('Link type not found for id ' + linkTypeId);
        return;
    }
    this.links[index][rowIndex].link_type = linkTypeId + '_' + linkTypeDirection;
    this.links[index][rowIndex].link_type_id = linkTypeId;
    this.links[index][rowIndex].link_type_direction = linkTypeDirection;
    this.links[index][rowIndex].link_type_label = linkTypeObj[linkTypeDirection];
    this.links[index][rowIndex].editMode = false;
};
