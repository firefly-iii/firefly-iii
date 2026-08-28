import Get from "../../../api/model/link-type/get.js";

export function loadLinkTypes() {
    let params = {
        page: 1, limit: 1337
    };
    let getter = new Get();
    return getter.list(params).then((response) => {
        let set = [];
        for (let i in response.data.data) {
            if (response.data.data.hasOwnProperty(i)) {
                let current = response.data.data[i];
                let entry = {
                    id: current.id,
                    name: current.attributes.name,
                    inward: current.attributes.inward,
                    outward: current.attributes.outward,
                    editable: current.attributes.editable,
                };
                set.push(entry);
            }
        }
        return set;
    });
}
