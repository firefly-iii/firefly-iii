<!--
  - GenericAttachments.vue
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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ title }}
    </div>
    <div class="input-group">
      <input
          :class="errors.length > 0 ? 'form-control is-invalid' : 'form-control'"
          :placeholder="title"
          :name="fieldName"
          multiple
          ref="att"
          @change="selectedFile"
          type="file"
          :disabled=disabled
      />
      <span class="input-group-btn">
            <button
                class="btn btn-default"
                type="button"
                v-on:click="clearAtt"><span class="far fa-trash-alt"></span></button>
        </span>
    </div>
    <span v-if="errors.length > 0">
      <span v-for="error in errors" class="text-danger small">{{ error }}<br/></span>
    </span>
  </div>
</template>

<script>
export default {
  name: "GenericAttachments",
  props: {
    title: {
      type: String,
      default: ''
    },
    disabled: {
      type: Boolean,
      default: false
    },
    fieldName: {
      type: String,
      default: ''
    },
    errors: {
      type: Array,
      default: function () {
        return [];
      }
    },
    uploadTrigger: {
      type: Boolean,
      default: false
    },
    uploadObjectType: {
      type: String,
      default: ''
    },
    uploadObjectId: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      localValue: this.value,
      uploaded: 0,
      uploads: 0,
    }
  },
  watch: {
    uploadTrigger: function (value) {
      if (true === value) {
        // this.createAttachment().then(response => {
        //   this.uploadAttachment(response.data.data.id, new Blob([evt.target.result]));
        // });

        // new code
        console.log('start of new');
        let files = this.$refs.att.files;
        this.uploads = files.length;
        // loop all files and create attachments.
        for (let i in files) {
          if (files.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
            console.log('Now at file ' + (parseInt(i) + 1) + ' / ' + files.length);
            // read file into file reader:
            let current = files[i];
            let fileReader = new FileReader();
            let theParent = this; // dont ask me why i need to do this.
            fileReader.onloadend = evt => {
              if (evt.target.readyState === FileReader.DONE) {
                console.log('I am done reading file ' + (parseInt(i) + 1));
                this.createAttachment(current.name).then(response => {
                  console.log('Created attachment. Now upload (1)');
                  return theParent.uploadAttachment(response.data.data.id, new Blob([evt.target.result]));
                }).then(theParent.countAttachment);
              }
            }
            fileReader.readAsArrayBuffer(current);
          }
        }
        if (0 === files.length) {
          console.log('No files to upload. Emit event!');
          this.$emit('uploaded-attachments', this.transaction_journal_id);
        }
        // Promise.all(promises).then(response => {
        //   console.log('All files uploaded. Emit event!');
        //   this.$emit('uploaded-attachments', this.transaction_journal_id);
        // });

        // end new code


      }
    },
  },
  methods: {
    countAttachment: function () {
      this.uploaded++;
      console.log('Uploaded ' + this.uploaded + ' / ' + this.uploads);
      if (this.uploaded >= this.uploads) {
        console.log('All files uploaded. Emit event for ' + this.uploadObjectId);
        this.$emit('uploaded-attachments', this.uploadObjectId);
      }
    },
    uploadAttachment: function (attachmentId, data) {
      this.created++;
      console.log('Now in uploadAttachment()');
      const uploadUri = './api/v1/attachments/' + attachmentId + '/upload';
      return axios.post(uploadUri, data)
    },
    createAttachment: function (name) {
      const uri = './api/v1/attachments';
      const data = {
        filename: name,
        attachable_type: this.uploadObjectType,
        attachable_id: this.uploadObjectId,
      };
      return axios.post(uri, data);
    },
    selectedFile: function () {
      this.$emit('selected-attachments');
    },
    clearAtt: function () {
      this.$refs.att.value = '';
      this.$emit('selected-no-attachments');
    },
  }
}
</script>

