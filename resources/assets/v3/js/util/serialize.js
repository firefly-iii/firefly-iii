export default function serialize(obj, prefix) {
    let str = [],
        p;
    for (p in obj) {
        if (Object.hasOwn(obj, p)) {
            let k = prefix ? prefix + "[" + p + "]" : p,
                v = obj[p];
            str.push(
                v !== null && typeof v === "object"
                    ? serialize(v, k)
                    : encodeURIComponent(k) + "=" + encodeURIComponent(v),
            );
        }
    }
    return str.join("&");
}
