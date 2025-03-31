/*
 * process-attachments.js
 * Copyright (c) 2024 james@firefly-iii.org
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

import AttachmentPost from "../../../api/v1/model/attachment/post.js";

let uploadFiles = function (fileData) {
    let count = fileData.length;
    let uploads = 0;
    let hasError = false;

    for (const key in fileData) {
        if (fileData.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294 && false === hasError) {
            let poster = new AttachmentPost();
            poster.post(fileData[key].name, 'TransactionJournal', fileData[key].journal).then(response => {
                let attachmentId = parseInt(response.data.data.id);
                poster.upload(attachmentId, fileData[key].content).then(attachmentResponse => {
                    uploads++;
                    if (uploads === count) {
                        const event = new CustomEvent('upload-success', {some: 'details'});
                        document.dispatchEvent(event);
                    }
                }).catch(error => {
                    console.error('Could not upload');
                    console.error(error);
                    uploads++;
                    // break right away
                    const event = new CustomEvent('upload-failed', {error: error});
                    document.dispatchEvent(event);
                    hasError = true;
                });
            }).catch(error => {
                console.error('Could not create upload.');
                console.error(error);
                uploads++;
                const event = new CustomEvent('upload-failed', {error: error});
                document.dispatchEvent(event);
                hasError = true;
            });
        }
    }
}


export function processAttachments(groupId, transactions) {
    // reverse list of transactions
    transactions = transactions.reverse();

    // array of all files to be uploaded:
    let toBeUploaded = [];
    let count = 0;
    // array with all file data.
    let fileData = [];

    // all attachments
    let attachments = document.querySelectorAll('input[name="attachments[]"]');

    // loop over all attachments, and add references to this array:
    for (const key in attachments) {
        if (attachments.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
            for (const fileKey in attachments[key].files) {
                if (attachments[key].files.hasOwnProperty(fileKey) && /^0$|^[1-9]\d*$/.test(fileKey) && fileKey <= 4294967294) {
                    // include journal thing.
                    toBeUploaded.push({
                        journal: transactions[key].transaction_journal_id,
                        file: attachments[key].files[fileKey]
                    });
                    count++;
                }
            }
        }
    }

    // loop all uploads. This is async.
    for (const key in toBeUploaded) {
        if (toBeUploaded.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {

            // create file reader thing that will read all of these uploads
            (function (f, key) {
                let fileReader = new FileReader();
                fileReader.onloadend = function (evt) {
                    if (evt.target.readyState === FileReader.DONE) { // DONE == 2
                        fileData.push({
                            name: toBeUploaded[key].file.name,
                            journal: toBeUploaded[key].journal,
                            content: new Blob([evt.target.result])
                        });
                        if (fileData.length === count) {
                            uploadFiles(fileData);
                        }
                    }
                };
                fileReader.readAsArrayBuffer(f.file);
            })(toBeUploaded[key], key,);
        }
    }
    return count;
}
