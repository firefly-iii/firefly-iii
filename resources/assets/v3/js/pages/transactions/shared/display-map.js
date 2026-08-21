export function displayMap(index) {
    index = parseInt(index);
    // show location?
    if (true === this.formBehaviour.customFields.location) {
        if(true === this.entries[index].hasLocation) {
            this.renderMap(index, false);
            return;
        }
        if (false === this.formBehaviour.defaultCoordinates.loaded) {
            // load first, then show map.
            this.loadDefaultCoordinates().then(data => {
                this.formBehaviour.defaultCoordinates = data;
                this.renderMap(index, true);
            });
            return;
        }
        this.renderMap(index, false);
    }
}
