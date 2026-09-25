import PostLink from "../../../api/model/transaction-link/post.js";
import PutLink from "../../../api/model/transaction-link/put.js";
import i18next from "i18next";

export function processTransactionLinks(transactions) {
    this.notifications.wait.show = true;
    this.notifications.wait.text = i18next.t("firefly.save_links_working");
    let count = 0;
    for (let i = 0; i < transactions.length; i++) {
        if (Object.hasOwn(transactions, i) && Object.hasOwn(this.links, i)) {
            let journalId = transactions[i];
            for (let j = 0; j < this.links[i].length; j++) {
                if (Object.hasOwn(this.links[i], j)) {
                    count++;
                    let link = this.links[i][j];
                    let inwardId = journalId;
                    let outwardId = parseInt(link.journal_id);
                    // console.log(
                    //     "Inward ID is now" + inwardId + " and outward ID is now " + outwardId + " and existing ID is " + link.id,
                    // );

                    if ("inward" === link.link_type_direction) {
                        inwardId = parseInt(link.journal_id);
                        outwardId = journalId;
                        // console.log(
                        //     "Switched because inward: inward ID is now" +
                        //     inwardId +
                        //     " and outward ID is now " +
                        //     outwardId,
                        // );
                    }
                    if (0 === link.id) {
                        // console.log('Link.id = ' + link.id + ' so we will create a new link with inwardId = ' + inwardId + ' and outwardId = ' + outwardId);
                        new PostLink()
                            .post(link.link_type_id, inwardId, outwardId, null)
                            .then(() => {
                                this.redirectAfterTransactionLinks(i, j);
                            })
                            .catch(function (e) {
                                console.error(e);
                            });
                    }
                    if (link.id > 0) {
                        // console.log('Link.id = ' + link.id + ' so we will PUT link ' + link.id + ' with inwardId = ' + inwardId + ' and outwardId = ' + outwardId);
                        new PutLink()
                            .put(
                                {
                                    link_type_id: link.link_type_id,
                                    inward_id: inwardId,
                                    outward_id: outwardId,
                                },
                                { id: link.id },
                                null,
                            )
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
