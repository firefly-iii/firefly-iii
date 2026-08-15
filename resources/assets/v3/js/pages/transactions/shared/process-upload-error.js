/*
 * process-upload-error.js
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

import i18next from "i18next";
import Delete from "../../../api/model/transaction/delete.js";

export function processUploadError(event){
    this.notifications.success.show = false;
    this.notifications.wait.show = false;
    this.notifications.error.show = true;
    this.formStates.isSubmitting = false;
    this.notifications.error.text = i18next.t('firefly.errors_upload');
    console.log(event.detail.error.response.status);
    if(413 === event.detail.error.response.status) {
        this.notifications.error.text = i18next.t('firefly.upload_too_large');
    }
    if('create' === this.formBehaviour.formType) {
        // delete transaction and let user try again.
        let del = new Delete();
        del.delete(this.groupProperties.id);
    }
}
