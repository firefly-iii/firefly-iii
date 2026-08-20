
import Preferences from '../../../api/preferences/index.js';

export function loadCustomFields() {
    return (new Preferences()).getByName('transaction_journal_optional_fields').then(data => {
        return data.data.data.attributes.data;
    });
}
