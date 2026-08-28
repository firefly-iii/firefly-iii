
import Get from '../../../api/model/transaction/get.js';
export function loadTransactionLinks(index, journalId) {
    console.log('Get transaction links for index #' + index + ' and journal #' + journalId);
    (new Get).transactionLinks(journalId).then((data) => {
        let links = data.data.data;
        for(let i = 0;i< links.length; i++) {
            if(links.hasOwnProperty(i)) {
                let current = links[i];
                console.log(current);
                console.log(this.formData.linkTypes);
                let direction = parseInt(journalId) === parseInt(current.attributes.inward_id) ? 'inward' : 'outward';
                let otherJournal = parseInt(current.attributes.inward_id)
                if(parseInt(journalId) === parseInt(current.attributes.inward_id)) {
                    otherJournal = parseInt(current.attributes.outward_id);
                }
                (new Get).showJournal(otherJournal).then((response) => {
                    let group =response.data.data;
                    let foundJournal = null;
                    for(let j=0;j<group.attributes.transactions.length;j++) {
                        if(group.attributes.transactions.hasOwnProperty(j)) {
                            let journal = group.attributes.transactions[j];
                            if(parseInt(journal.transaction_journal_id) === otherJournal) {
                                foundJournal = journal;
                            }
                        }
                    }
                    if(null !== foundJournal) {
                        console.log('showJournal');
                    this.links[index].push(
                        {
                            id: parseInt(current.id),
                            link_type: parseInt(current.attributes.link_type_id) + '_' + direction,
                            link_type_id: parseInt(current.attributes.link_type_id),
                            link_type_direction: direction,
                            link_type_label: this.formData.linkTypes.find(link => link.id === parseInt(current.attributes.link_type_id))[direction],
                            journal_id: parseInt(journalId),
                            group_id: parseInt(group.id),
                            journal_description: foundJournal.description,
                            editMode: false,
                            stored: false,
                        }
                    );
                    }
                } );


            }
        }
    });
}
