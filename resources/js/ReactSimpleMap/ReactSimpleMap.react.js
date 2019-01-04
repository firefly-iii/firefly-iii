// @flow

import * as React from "react";
import {
  ComposableMap,
  ZoomableGroup,
  Geographies,
  Geography,
} from "react-simple-maps";
import data from "./data/world-50m.json";
import { scaleLinear } from "d3-scale";

const wrapperStyles = {
  width: "100%",
  height: "auto",
  maxWidth: "100%",
  margin: "0 auto",
  fontFamily: "Roboto, sans-serif",
};

type State = {
  origin: { x: number, y: number },
  content: string,
};

const popScale = scaleLinear()
  .domain([0, 100000000, 1400000000])
  .range(["#CFD8DC", "#607D8B", "#37474F"]);

class ReactSimpleMap extends React.PureComponent<void, State> {
  state = {
    origin: { x: 0, y: 0 },
    content: "",
  };

  handleMove = (
    geography: { properties: { name: string, pop_est: string } },
    evt: SyntheticMouseEvent<>
  ): void => {
    const x = evt.clientX;
    const y = evt.clientY + window.pageYOffset;
    this.setState({
      origin: { x, y },
      content: geography.properties.name + ": " + geography.properties.pop_est,
    });
  };

  handleLeave = (): void => {
    this.setState({ content: "" });
  };

  render() {
    return (
      <div style={wrapperStyles}>
        {this.state.content && (
          <div
            style={{
              position: "fixed",
              top: this.state.origin.y + 20 - window.scrollY,
              left: this.state.origin.x,
              zIndex: 999999,
              textAlign: "center",
              border: "1px grey solid",
              borderRadius: 3,
              padding: 4,
              backgroundColor: "#fff",
            }}
          >
            {this.state.content}
          </div>
        )}
        <ComposableMap
          projectionConfig={{
            scale: 205,
            rotation: [-11, 0, 0],
          }}
          style={{
            width: "100%",
            height: "auto",
          }}
          width={900}
        >
          <ZoomableGroup center={[0, 20]}>
            <Geographies geography={data}>
              {(geographies, projection) =>
                geographies.map((geography, i) => (
                  <Geography
                    key={i}
                    geography={geography}
                    projection={projection}
                    onMouseMove={this.handleMove}
                    onMouseLeave={this.handleLeave}
                    style={{
                      default: {
                        fill: popScale(geography.properties.pop_est),
                        stroke: "#607D8B",
                        strokeWidth: 0.75,
                        outline: "none",
                      },
                      hover: {
                        fill: "#263238",
                        stroke: "#607D8B",
                        strokeWidth: 0.75,
                        outline: "none",
                      },
                      pressed: {
                        fill: "#263238",
                        stroke: "#607D8B",
                        strokeWidth: 0.75,
                        outline: "none",
                      },
                    }}
                  />
                ))
              }
            </Geographies>
          </ZoomableGroup>
        </ComposableMap>
      </div>
    );
  }
}

export default ReactSimpleMap;
