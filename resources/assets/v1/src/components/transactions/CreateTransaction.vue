<!--
  - CreateTransaction.vue
  - Copyright (c) 2019 james@firefly-iii.org
  -
  - This file is part of Firefly III (https://github.com/firefly-iii).
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <https://www.gnu.org/licenses/>.
  -->
<script>
export default {
    name: "CreateTransaction",
    methods: {
        // submit transaction
        collectAttachmentData(response) {
            // console.log('Now incollectAttachmentData()');
            let groupId = response.data.data.id;

            // reverse list of transactions?
            response.data.data.attributes.transactions = response.data.data.attributes.transactions.reverse();
            // array of all files to be uploaded:
            let toBeUploaded = [];

            // array with all file data.
            let fileData = [];

            // all attachments
            let attachments = $('input[name="attachments[]"]');

            // loop over all attachments, and add references to this array:
            for (const key in attachments) {
                if (attachments.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    for (const fileKey in attachments[key].files) {
                        if (attachments[key].files.hasOwnProperty(fileKey) && /^0$|^[1-9]\d*$/.test(fileKey) && fileKey <= 4294967294) {
                            // include journal thing.
                            toBeUploaded.push(
                                {
                                    journal: response.data.data.attributes.transactions[key].transaction_journal_id,
                                    file: attachments[key].files[fileKey]
                                }
                            );
                        }
                    }
                }
            }
            let count = toBeUploaded.length;
            // console.log('Found ' + toBeUploaded.length + ' attachments.');

            // loop all uploads.
            for (const key in toBeUploaded) {
                if (toBeUploaded.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    // create file reader thing that will read all of these uploads
                    (function (f, i, theParent) {
                        let fileReader = new FileReader();
                        fileReader.onloadend = function (evt) {
                            if (evt.target.readyState === FileReader.DONE) { // DONE == 2
                                fileData.push(
                                    {
                                        name: toBeUploaded[key].file.name,
                                        journal: toBeUploaded[key].journal,
                                        content: new Blob([evt.target.result])
                                    }
                                );
                                if (fileData.length === count) {
                                    theParent.uploadFiles(fileData, groupId, response.data.data);
                                }
                            }
                        };
                        fileReader.readAsArrayBuffer(f.file);
                    })(toBeUploaded[key], key, this);
                }
            }
            return count;
        },

        uploadFiles(fileData, groupId, transactionData) {
            let count = fileData.length;
            let uploads = 0;
            for (const key in fileData) {
                if (fileData.hasOwnProperty(key) && /^0$|^[1-9]\d*$/.test(key) && key <= 4294967294) {
                    // console.log('Creating attachment #' + key);
                    // axios thing, + then.
                    const uri = './api/v1/attachments';
                    const data = {
                        filename: fileData[key].name,
                        attachable_type: 'TransactionJournal',
                        attachable_id: fileData[key].journal,
                    };
                    axios.post(uri, data)
                        .then(response => {
                            // console.log('Created attachment #' + key);
                            // console.log('Uploading attachment #' + key);
                            const uploadUri = './api/v1/attachments/' + response.data.data.id + '/upload';
                            axios.post(uploadUri, fileData[key].content)
                                .then(attachmentResponse => {
                                    // console.log('Uploaded attachment #' + key);
                                    uploads++;
                                    if (uploads === count) {
                                        // finally we can redirect the user onwards.
                                        // console.log('FINAL UPLOAD');
                                        this.redirectUser(groupId, transactionData);
                                    }
                                    // console.log('Upload complete!');
                                    return true;
                                }).catch(error => {
                                console.error('[b] Could not upload');
                                console.error(error);
                                // console.log('Uploaded attachment #' + key);
                                uploads++;
                                if (uploads === count) {
                                    // finally we can redirect the user onwards.
                                    // console.log('FINAL UPLOAD');
                                    this.redirectUser(groupId, transactionData);
                                }
                                // console.log('Upload complete!');
                                return false;
                            });
                        }).catch(error => {
                        console.error('Could not create upload.');
                        console.error(error);
                        uploads++;
                        if (uploads === count) {
                            // finally we can redirect the user onwards.
                            // console.log('FINAL UPLOAD');
                            this.redirectUser(groupId, transactionData);
                        }
                        // console.log('Upload complete!');
                        return false;
                    });
                }
            }

        },
    },
}
</script>
