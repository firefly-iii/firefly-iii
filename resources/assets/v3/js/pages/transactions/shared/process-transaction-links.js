import PostLink from "../../../api/model/transaction-link/post.js";

export function processTransactionLinks(transactions) {
    let count = 0;
    for (let i = 0; i < transactions.length; i++) {
        if (Object.hasOwn(transactions, i) && Object.hasOwn(this.links, i)) {
            let journalId = transactions[i];
            for (let j = 0; j < this.links[i].length; j++) {
                if (Object.hasOwn(this.links[i], j)) {
                    count++;
                    let link = this.links[i][j];
                    let left = journalId;
                    let right = parseInt(link.journal_id);
                    console.log('Left is now' + left + ' and right is now ' + right + ' and existing ID is ' + link.id);

                    if ("inward" === link.link_type_direction) {
                        left = parseInt(link.journal_id);
                        right = journalId;
                        console.log('Switched because inward: left is now' + left + ' and right is now ' + right);
                    }
                    if(0 === link.id) {
                        new PostLink()
                            .post(link.link_type_id, left, right, null)
                            .then(() => {
                                this.redirectAfterTransactionLinks(i, j);
                            })
                            .catch(function (e) {
                                console.error(e);
                            });
                    }
                }
            }
        }
    }
    if (0 === count) {
        this.formStates.storedLinks = true;
    }
}
