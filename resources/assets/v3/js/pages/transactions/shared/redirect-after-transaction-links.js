export function redirectAfterTransactionLinks(oldI, oldJ) {
    this.links[oldI][oldJ].stored = true;
    let completed = true;
    for (let i = 0; i < this.links.length; i++) {
        if (this.links.hasOwnProperty(i)) {
            for (let j = 0; j < this.links[i].length; j++) {
                if (this.links[i].hasOwnProperty(j)) {
                    if (false === this.links[i][i].stored) {
                        completed = false;
                    }
                }
            }
        }
    }
    if (true === completed) {
        this.formStates.storedLinks = completed;
        this.showMessageOrRedirectUser();
    }
};
