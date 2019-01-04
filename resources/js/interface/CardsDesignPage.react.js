// @flow

import * as React from "react";

import { Page, Grid, Card, Button, Form, Dimmer } from "tabler-react";

import SiteWrapper from "../SiteWrapper.react";

function CardsDesignPage(): React.Node {
  return (
    <SiteWrapper>
      <Page.Content>
        <Grid.Row>
          <Grid.Col md={6} xl={4}>
            <Card
              title="This is a standard card"
              isCollapsible
              isClosable
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
              footer="This is standard card footer"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Built card"
              isCollapsible
              isClosable
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Card blue"
              isCollapsible
              isClosable
              statusColor="blue"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Card green"
              isCollapsible
              isClosable
              statusColor="green"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Card orange"
              isCollapsible
              isClosable
              statusColor="orange"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Card red"
              isCollapsible
              isClosable
              statusColor="red"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Card yellow"
              isCollapsible
              isClosable
              statusColor="yellow"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Card teal"
              isCollapsible
              isClosable
              statusColor="teal"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Card purple"
              isCollapsible
              isClosable
              statusColor="purple"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              title="Card status on left side"
              isCollapsible
              isClosable
              statusColor="blue"
              statusSide
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              isCollapsed
              isCollapsible
              isClosable
              title="Initial isCollapsibled card"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col md={6} xl={4}>
            <Card
              isFullscreenable
              isClosable
              isCollapsible
              title="With additional fullscreen button"
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col lg={6}>
            <Card>
              <Card.Header>
                <Card.Title>Panel with custom buttons</Card.Title>
                <Card.Options>
                  <Button RootComponent="a" color="primary" size="sm">
                    Action 1
                  </Button>
                  <Button
                    RootComponent="a"
                    color="secondary"
                    size="sm"
                    className="ml-2"
                  >
                    Action 2
                  </Button>
                </Card.Options>
              </Card.Header>
              <Card.Body>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                Aperiam deleniti fugit incidunt, iste, itaque minima neque
                pariatur perferendis sed suscipit velit vitae voluptatem. A
                consequuntur, deserunt eaque error nulla temporibus!
              </Card.Body>
            </Card>
          </Grid.Col>
          <Grid.Col lg={6}>
            <Card>
              <Card.Header>
                <Card.Title>Card with search form</Card.Title>
                <Card.Options>
                  <Form>
                    <Form.InputGroup>
                      <Form.Input
                        className="form-control-sm"
                        placeholder="Search something..."
                        name="s"
                      />
                      <span className="input-group-btn ml-2">
                        <Button
                          size="sm"
                          color="default"
                          type="submit"
                          icon="search"
                        />
                      </span>
                    </Form.InputGroup>
                  </Form>
                </Card.Options>
              </Card.Header>
              <Card.Body>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                Aperiam deleniti fugit incidunt, iste, itaque minima neque
                pariatur perferendis sed suscipit velit vitae voluptatem. A
                consequuntur, deserunt eaque error nulla temporibus!
              </Card.Body>
            </Card>
          </Grid.Col>
          <Grid.Col lg={6} xl={4}>
            <Card title="Card with alert" isClosable isCollapsible>
              <Card.Alert color="success">
                Adding action was successful
              </Card.Alert>
              <Card.Body>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                Aperiam deleniti fugit incidunt, iste, itaque minima neque
                pariatur perferendis sed suscipit velit vitae voluptatem. A
                consequuntur, deserunt eaque error nulla temporibus!
              </Card.Body>
            </Card>
          </Grid.Col>
          <Grid.Col lg={6} xl={4}>
            <Card
              alert="Adding action failed"
              alertColor="danger"
              title="Card with alert"
              isCollapsible
              isClosable
              body="Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            Aperiam deleniti fugit incidunt, iste, itaque minima neque
            pariatur perferendis sed suscipit velit vitae voluptatem. A
            consequuntur, deserunt eaque error nulla temporibus!"
            />
          </Grid.Col>
          <Grid.Col lg={6} xl={4}>
            <Card>
              <Card.Header>
                <Card.Title>Card with switch</Card.Title>
                <Card.Options>
                  <Form.Switch value="1" className="m-0" />
                </Card.Options>
              </Card.Header>
              <Card.Body>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                Aperiam deleniti fugit incidunt, iste, itaque minima neque
                pariatur perferendis sed suscipit velit vitae voluptatem. A
                consequuntur, deserunt eaque error nulla temporibus!
              </Card.Body>
            </Card>
          </Grid.Col>
          <Grid.Col lg={6} xl={4}>
            <Card title="Card with loader" isClosable isCollapsible>
              <Card.Body>
                <Dimmer active loader>
                  Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                  Aperiam deleniti fugit incidunt, iste, itaque minima neque
                  pariatur perferendis sed suscipit velit vitae voluptatem. A
                  consequuntur, deserunt eaque error nulla temporibus!
                </Dimmer>
              </Card.Body>
            </Card>
          </Grid.Col>
        </Grid.Row>
      </Page.Content>
    </SiteWrapper>
  );
}

export default CardsDesignPage;
