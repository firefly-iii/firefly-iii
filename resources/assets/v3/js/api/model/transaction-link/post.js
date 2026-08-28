

import {api} from "../../../boot/axios";

export default class Post {
    post(linkTypeId, inwardId, outwardId, notes) {
        console.log('POST!');
        let url = '/api/v1/transaction-links';
        return api.post(url, {
            link_type_id: parseInt(linkTypeId),
            inward_id: parseInt(inwardId),
            outward_id: parseInt(outwardId),
            notes: notes
        });
    }

}
