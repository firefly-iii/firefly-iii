
import L from 'leaflet';
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerRetinaIcon from "leaflet/dist/images/marker-icon-2x.png";
import shadow from 'leaflet/dist/images/marker-shadow.png';
export function onMapZoom(event) {
    let index = parseInt(event.target._container.dataset.index);
    this.entries[index].zoom_level = this.maps[index].getZoom();
}
