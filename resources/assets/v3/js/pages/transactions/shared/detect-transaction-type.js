export function detectTransactionType() {
    const sourceType = this.entries[0].source_account.type ?? 'unknown';
    const destType = this.entries[0].destination_account.type ?? 'unknown';
    if ('unknown' === sourceType && 'unknown' === destType) {
        this.groupProperties.transactionType = 'unknown';
        console.warn('Cannot infer transaction type from two unknown accounts.');
        this.disableSplitAccounts();
        return;
    }

    // transfer: both are the same and in strict set of account types
    if (sourceType === destType && ['Asset account', 'Loan', 'Debt', 'Mortgage'].includes(sourceType)) {
        this.groupProperties.transactionType = 'transfer';
        console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');

        // this also locks the amount into the amount of the source account
        // and the foreign amount (if different) in that of the destination account.
        console.log('filter down currencies for transfer.');
        this.determineAmountCurrency(this.entries[0].source_account.currency_code);
        this.filterForeignCurrencies(this.entries[0].destination_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    // withdrawals:
    if ('Asset account' === sourceType && ['Expense account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
        this.groupProperties.transactionType = 'withdrawal';
        console.log('[a] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].source_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    if ('Asset account' === sourceType && 'unknown' === destType) {
        this.groupProperties.transactionType = 'withdrawal';
        console.log('[b] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].source_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Expense account' === destType) {
        this.groupProperties.transactionType = 'withdrawal';
        console.log('[c] Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].source_account.currency_code);
        this.disableSplitAccounts();
        return;
    }

    // deposits:
    if ('Revenue account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
        this.groupProperties.transactionType = 'deposit';
        console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.disableSplitAccounts();
        this.determineAmountCurrency(this.entries[0].destination_account.currency_code);
        return;
    }
    if ('unknown' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
        this.groupProperties.transactionType = 'deposit';
        console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].destination_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    if ('Expense account' === sourceType && ['Asset account', 'Debt', 'Loan', 'Mortgage'].includes(destType)) {
        this.groupProperties.transactionType = 'deposit';
        console.warn('FORCE transaction type to be "' + this.groupProperties.transactionType + '".');
        this.entries[0].source_account.id = '';
        this.determineAmountCurrency(this.entries[0].destination_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    if (['Debt', 'Loan', 'Mortgage'].includes(sourceType) && 'Asset account' === destType) {
        this.groupProperties.transactionType = 'deposit';
        console.log('Transaction type is detected to be "' + this.groupProperties.transactionType + '".');
        this.determineAmountCurrency(this.entries[0].destination_account.currency_code);
        this.disableSplitAccounts();
        return;
    }
    console.warn('Unknown account combination between "' + sourceType + '" and "' + destType + '".');
    this.disableSplitAccounts();
};
