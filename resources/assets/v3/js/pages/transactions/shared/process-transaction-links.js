import PostLink from "../../../api/model/transaction-link/post.js";

export function processTransactionLinks(transactions) {
    let count = 0;
    for (let i = 0; i < transactions.length; i++) {
        if (transactions.hasOwnProperty(i) && this.links.hasOwnProperty(i)) {
            let journalId = transactions[i];
            for (let j = 0; j < this.links[i].length; j++) {
                if (this.links[i].hasOwnProperty(j)) {
                    count++;
                    let link = this.links[i][j];
                    let left = journalId;
                    let right = parseInt(link.journal_id);
                    if ('inward' === link.link_type_direction) {
                        left = parseInt(link.journal_id);
                        right = journalId;
                    }
                    (new PostLink).post(link.link_type_id, left, right, null).then(() => {
                        this.redirectAfterTransactionLinks(i, j);
                    }).catch(function (e) {
                        console.error(e);
                    });
                }
            }
        }
    }
    if (0 === count) {
        this.formStates.storedLinks = true;
    }
}
