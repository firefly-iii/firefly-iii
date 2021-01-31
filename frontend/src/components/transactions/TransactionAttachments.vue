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
  <div class="form-group">
    <div class="text-xs d-none d-lg-block d-xl-block">
      {{ $t('firefly.attachments') }}
    </div>
    <div class="input-group">
      <input
          type="file"
          multiple
          ref="att"
          name="attachments[]"
          class="form-control"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: "TransactionAttachments",
  props: ['transaction_journal_id'],
  watch: {
    transaction_journal_id: function (value) {
      // do upload!
      if (0 !== value) {
        this.doUpload();
      }
    }
  },
  methods: {
    doUpload: function () {
      let uploads = [];
      for (let i in this.$refs.att.files) {
        if (this.$refs.att.files.hasOwnProperty(i) && /^0$|^[1-9]\d*$/.test(i) && i <= 4294967294) {
          let current = this.$refs.att.files[i];
          let fileReader = new FileReader();
          let theParent = this; // dont ask me why i need to do this.
          fileReader.onloadend = function (evt) {
            if (evt.target.readyState === FileReader.DONE) {
              // do upload here
              const uri = './api/v1/attachments';
              const data = {
                filename: current.name,
                attachable_type: 'TransactionJournal',
                attachable_id: theParent.transaction_journal_id,
              };
              // create new attachment:
              axios.post(uri, data).then(response => {
                // upload actual file:
                const uploadUri = './api/v1/attachments/' + response.data.data.id + '/upload';
                axios
                    .post(uploadUri, new Blob([evt.target.result]))
                    .then(attachmentResponse => {
                      // TODO feedback etc.
                      theParent.$emit('uploaded-attachments');
                    });
              });
            }
          }
          fileReader.readAsArrayBuffer(current);
        }
      }
      if (0 === this.$refs.att.files.length) {
        this.$emit('uploaded-attachments', this.transaction_journal_id);
      }
    }
  }

}
</script>

<style scoped>

</style>