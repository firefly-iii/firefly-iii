import {getAccount} from "./create-empty-split.js";

export function clearSourceAccount(index) {
    this.entries[index].source_account = getAccount();
    this.detectTransactionType();
}

export function clearDestinationAccount(index) {
    this.entries[index].destination_account = getAccount();
    this.detectTransactionType();
};
