export function clearLocation(e) {

    let index = parseInt(e.currentTarget.dataset.index);
    this.entries[index].hasLocation = false;
    this.entries[index].latitude = null;
    this.entries[index].longitude = null;
    this.entries[index].zoom_level = null;

    if(this.markers.hasOwnProperty(index)) {
        this.maps[index].removeLayer(this.markers[index]);
    }
}
