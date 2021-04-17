<!--
  - TransactionAttachments.vue
  - Copyright (c) 2021 james@firefly-iii.org
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

<template>
  <div v-if="showField" class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.attachments') }}
    </div>
    <div class="input-group">
      <input
          ref="att"
          class="form-control"
          multiple
          @change="selectedFile"
          name="attachments[]"
          type="file"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: "TransactionAttachments",
  props: ['transaction_journal_id', 'customFields', 'index', 'uploadTrigger', 'clearTrigger'],
  data() {
    return {
      availableFields: this.customFields,
      uploads: 0,
      created: 0,
      uploaded: 0,
    }
  },
  watch: {
    customFields: function (value) {
      this.availableFields = value;
    },
    uploadTrigger: function () {
      //console.log('uploadTrigger(' + this.transaction_journal_id + ',' + this.index + ')');
      this.doUpload();
    },
    clearTrigger: function () {
      //console.log('clearTrigger(' + this.transaction_journal_id + ',' + this.index + ')');
      this.$refs.att.value = null;
    },
    transaction_journal_id: function (value) {
      //console.log('watch transaction_journal_id: ' + value + ' (index ' + this.index + ')');
    }
  },
  computed: {
    showField: function () {
      if ('attachments' in this.availableFields) {
        return this.availableFields.attachments;
      }
      return false;
    }
  },
  methods: {
    selectedFile: function () {
      this.$emit('selected-attachments', {index: this.index, id: this.transaction_journal_id});
    },
    createAttachment: function (name) {
      // console.log('Now in createAttachment()');
      const uri = './api/v1/attachments';
      const data = {
        filename: name,
        attachable_type: 'TransactionJournal',
        attachable_id: this.transaction_journal_id,
      };
      // create new attachment:
      return axios.post(uri, data);
    },
    uploadAttachment: function (attachmentId, data) {
      this.created++;
      // console.log('Now in uploadAttachment()');
      const uploadUri = './api/v1/attachments/' + attachmentId + '/upload';
      return axios.post(uploadUri, data)
    },
    countAttachment: function () {
      this.uploaded++;
      //console.log('Uploaded ' + this.uploaded + ' / ' + this.uploads);
      if (this.uploaded >= this.uploads) {
        //console.log('All files uploaded. Emit event for ' + this.transaction_journal_id + '(' + this.index + ')');
        this.$emit('uploaded-attachments', this.transaction_journal_id);
      }
    },
    doUpload: function () {
      let files = this.$refs.att.files;
      this.uploads = files.length;
      // loop all files and create attachments.
      for (let i in files) {
        if (files.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          // console.log('Now at file ' + (parseInt(i) + 1) + ' / ' + files.length);
          // read file into file reader:
          let current = files[i];
          let fileReader = new FileReader();
          let theParent = this; // dont ask me why i need to do this.
          fileReader.onloadend = evt => {
            if (evt.target.readyState === FileReader.DONE) {
              // console.log('I am done reading file ' + (parseInt(i) + 1));
              this.createAttachment(current.name).then(response => {
                // console.log('Created attachment. Now upload (1)');
                return theParent.uploadAttachment(response.data.data.id, new Blob([evt.target.result]));
              }).then(theParent.countAttachment);
            }
          }
          fileReader.readAsArrayBuffer(current);
        }
      }
      if (0 === files.length) {
        //console.log('No files to upload. Emit event!');
        this.$emit('uploaded-attachments', this.transaction_journal_id);
      }
      // Promise.all(promises).then(response => {
      //   console.log('All files uploaded. Emit event!');
      //   this.$emit('uploaded-attachments', this.transaction_journal_id);
      // });
    }
  }

}
</script>

<style scoped>

</style>