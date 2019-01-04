// @flow

import * as React from "react";
import cn from "classnames";

import "./GoogleMap.css";

import {
  withScriptjs,
  withGoogleMap,
  GoogleMap as ReactGoogleMap,
} from "react-google-maps";

const MapComponent: React.ElementType = withScriptjs(
  withGoogleMap(props => (
    <ReactGoogleMap
      defaultZoom={8}
      defaultCenter={{ lat: -34.397, lng: 150.644 }}
      disableDefaultUI={true}
    />
  ))
);

type Props = {|
  +blackAndWhite?: boolean,
|};

function GoogleMap({ blackAndWhite }: Props): React.Node {
  const containerClasses = cn("GoogleMapContainer", { blackAndWhite });
  return (
    <MapComponent
      googleMapURL="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=geometry,drawing,places"
      loadingElement={<div style={{ height: `100%` }} />}
      containerElement={<div className={containerClasses} />}
      mapElement={<div style={{ height: `100%` }} />}
    />
  );
}

export default GoogleMap;
