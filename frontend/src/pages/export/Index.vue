<template>
  <q-page>
    <div class="row q-mx-md">
      <div class="col-12">
        <q-card bordered>
          <q-card-section>
            <div class="text-h6">Export page</div>
          </q-card-section>
          <q-card-section>
            <p>
              Just to see if this works. Button defaults to this year.
            </p>
          </q-card-section>
          <q-card-section>
            <p>
              <q-btn @click="downloadTransactions">Download transactions</q-btn>
            </p>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import Export from "../../api/data/export";
import startOfYear from "date-fns/startOfYear";
import endOfYear from "date-fns/endOfYear";
import format from "date-fns/format";

export default {
  name: "Index",
  methods: {
    downloadTransactions: function () {
      let exp = new Export;
      let start = format(startOfYear(new Date), 'yyyy-MM-dd');
      let end = format(endOfYear(new Date), 'yyyy-MM-dd');
      exp.transactions(start, end).then((response) => {
        let label = 'export-transactions.csv';
        const blob = new Blob([response.data], {type: 'application/octet-stream'})
        const link = document.createElement('a')
        link.href = URL.createObjectURL(blob)
        link.download = label;
        link.click()
        URL.revokeObjectURL(link.href)
      });
    }
  }
}
</script>

<style scoped>

</style>
