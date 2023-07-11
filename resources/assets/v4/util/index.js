
const domContentLoadedCallbacks = [];
// from admin LTE
const onDOMContentLoaded = (callback) => {
    if (document.readyState === 'loading') {
        // add listener on the first call when the document is in loading state
        if (!domContentLoadedCallbacks.length) {
            document.addEventListener('DOMContentLoaded', () => {
                for (const callback of domContentLoadedCallbacks) {
                    callback()
                }
            })
        }

        domContentLoadedCallbacks.push(callback)
    } else {
        callback()
    }
}


export {
    onDOMContentLoaded,
}
