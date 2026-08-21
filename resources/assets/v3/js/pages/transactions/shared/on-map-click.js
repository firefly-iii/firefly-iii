
import L from 'leaflet';
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerRetinaIcon from "leaflet/dist/images/marker-icon-2x.png";
import shadow from 'leaflet/dist/images/marker-shadow.png';
export function onMapClick(event) {

    L.Marker.prototype.setIcon(L.icon({
        iconUrl: markerIcon,
        iconRetinaIcon: markerRetinaIcon,
        shadowUrl: shadow,
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    }));


    let index = parseInt(event.originalEvent.currentTarget.dataset.index);
    this.entries[index].hasLocation = true;
    this.entries[index].latitude = event.latlng.lat;
    this.entries[index].longitude = event.latlng.lng;
    this.entries[index].zoom_level = this.maps[index].getZoom();

    // add marker.
    if(this.markers.hasOwnProperty(index)) {
        this.maps[index].removeLayer(this.markers[index]);
    }
    this.markers[index] = L.marker(event.latlng);
    this.markers[index].addTo(this.maps[index]);

}
