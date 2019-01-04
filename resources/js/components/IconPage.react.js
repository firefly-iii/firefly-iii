// @flow

import * as React from "react";

import { Page, Grid, Card, Icon } from "tabler-react";

import faIcons from "../data/icons/fa";
import feIcons from "../data/icons/fe";
import flagIcons from "../data/icons/flag";
import paymentIcons from "../data/icons/payment";
import SiteWrapper from "../SiteWrapper.react";

const iconSets: Array<{
  prefix: "fa" | "fe" | "flag" | "payment",
  title: string,
  icons: Array<string>,
  description?: string,
  link?: string,
}> = [
  {
    prefix: "fe",
    title: "Feather Icons",
    icons: feIcons,
    description: "Simply beautiful open source icons.",
    link: "https://feathericons.com",
  },
  {
    prefix: "fa",
    title: "Font Awesome",
    icons: faIcons,
    description: "Powered by Font Awesome set.",
    link: "http://fontawesome.io",
  },
  { prefix: "flag", title: "Flags", icons: flagIcons },
  { prefix: "payment", title: "Payments", icons: paymentIcons },
];

function IconPage(): React.Node {
  return (
    <SiteWrapper>
      <Page.Content title="Icons">
        {iconSets.map(iconSet => (
          <Card key={iconSet.prefix}>
            <Card.Header>
              <Card.Title>{iconSet.title}</Card.Title>
            </Card.Header>
            <Card.Body>
              <Grid.Row>
                <Grid.Col lg={3}>
                  <p>
                    {iconSet.description}
                    {iconSet.link && (
                      <span>
                        {" "}
                        For more info{" "}
                        <a
                          href={iconSet.link}
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          click here
                        </a>.
                      </span>
                    )}
                  </p>
                  <p>
                    <code>{`<Icon prefix="${
                      iconSet.prefix
                    }" name="ICON_NAME" />`}</code>
                  </p>
                </Grid.Col>
                <Grid.Col lg={9}>
                  <div className="icons-list-wrap">
                    <ul className="icons-list">
                      {iconSet.icons.map(icon => (
                        <li className="icons-list-item" key={icon}>
                          <Icon prefix={iconSet.prefix} name={icon} />
                        </li>
                      ))}
                    </ul>
                  </div>
                </Grid.Col>
              </Grid.Row>
            </Card.Body>
          </Card>
        ))}
      </Page.Content>
    </SiteWrapper>
  );
}

export default IconPage;
