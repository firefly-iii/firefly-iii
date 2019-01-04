// @flow

import * as React from "react";

import {
  Container,
  Page,
  Grid,
  Card,
  Stamp,
  ContactCard,
  Timeline,
} from "tabler-react";

import SiteWrapper from "../SiteWrapper.react";

import GoogleMap from "../GoogleMap";

import ReactSimpleMap from "../ReactSimpleMap";

function MapCardsPage(): React.Node {
  return (
    <SiteWrapper>
      <div className="my-3 my-md-5">
        <Page.MapHeader>
          <GoogleMap blackAndWhite />
        </Page.MapHeader>
        <Container>
          <Grid.Row cards>
            <Grid.Col lg={4} md={6}>
              <ContactCard
                cardTitle="Client card"
                mapPlaceholder="./demo/staticmap.png"
                rounded
                objectURL="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2264%22%20height%3D%2264%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2064%2064%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_15ec911398e%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_15ec911398e%22%3E%3Crect%20width%3D%2264%22%20height%3D%2264%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2213.84375%22%20y%3D%2236.65%22%3E64x64%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E"
                alt="Generic placeholder image"
                name={"Axa Global Group"}
                address={{
                  line1: "1290 Avenua of The Americas",
                  line2: "New York, NY 101040105",
                }}
                details={[
                  { title: "Relationship", content: "Client" },
                  { title: "Business Type", content: "Insurance Company" },
                  {
                    title: "Website",
                    content: (
                      <a href="http://www.axa.com">http://www.axa.com</a>
                    ),
                  },
                  { title: "Office Phone", content: "+123456789" },
                ]}
                description={`Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                 Consectetur dignissimos doloribus eum fugiat itaque
                laboriosam maiores nisi nostrum perspiciatis vero.`}
              />
            </Grid.Col>
            <Grid.Col lg={8} md={6}>
              <Card title="World population map" body={<ReactSimpleMap />} />
              <Grid.Row>
                <Grid.Col width={6}>
                  <Card
                    title="Map of Warsaw metro"
                    options={<Stamp color="red">L2</Stamp>}
                    body={
                      <Timeline>
                        <Timeline.Item
                          title="Rondo Daszyńskiego"
                          badgeColor="red"
                          time="2 min. ago"
                        />
                        <Timeline.Item
                          title="Rondo ONZ"
                          badge
                          time="1 min. ago"
                        />
                        <Timeline.Item
                          title="Świętokrzyska"
                          badgeColor="blue"
                          time="now"
                          active
                          description="Lorem ipsum dolor sit amet, consectetur adipisicing elit."
                        />
                        <Timeline.Item
                          title="Nowy Świat-Uniwersytet"
                          badge
                          time="2 min."
                        />
                        <Timeline.Item
                          title="Centrum Nauki Kopernik"
                          badge
                          time="3 min."
                        />
                        <Timeline.Item
                          title="Stadion Narodowy"
                          badge
                          time="5 min."
                        />
                        <Timeline.Item
                          title="Dworzec Wileński"
                          badgeColor="green"
                          time="7 min."
                        />
                      </Timeline>
                    }
                  />
                </Grid.Col>
              </Grid.Row>
            </Grid.Col>
          </Grid.Row>
        </Container>
      </div>
    </SiteWrapper>
  );
}

export default MapCardsPage;
