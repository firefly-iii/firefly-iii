// @flow

import * as React from "react";

import { Page, Grid, StoreCard, Table, Card, Badge } from "tabler-react";

import SiteWrapper from "../SiteWrapper.react";

function StoreCardsPage(): React.Node {
  return (
    <SiteWrapper>
      <Page.Content title="Store Components">
        <Grid.Row>
          <Grid.Col lg={3}>
            <StoreCard
              title="Apple iPhone 7 Plus 256GB Red Special Edition"
              subtitle="Apple"
              price="$499"
              imgUrl="https://tabler.github.io/tabler/demo/products/apple-iphone7-special.jpg"
            />
            <StoreCard
              title="GoPro HERO6 Black"
              subtitle="GoPro"
              price="$599"
              imgUrl="https://tabler.github.io/tabler/demo/products/gopro-hero.jpg"
            />
          </Grid.Col>
          <Grid.Col lg={9}>
            <Card>
              <Table className="card-table table-vcenter">
                <Table.Body>
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/apple-iphone7-special.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>
                      Apple iPhone 7 Plus 256GB Red Special Edition
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      98 reviews
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      38 offers
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$499</strong>
                    </Table.Col>
                  </Table.Row>
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/apple-macbook-pro.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>
                      Apple MacBook Pro i7 3,1GHz/16/512/Radeon 560 Space Gray{" "}
                      <Badge color="default"> New </Badge>
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      97 reviews
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      13 offers
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$1499</strong>
                    </Table.Col>
                  </Table.Row>
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/apple-iphone7.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>Apple iPhone 7 32GB Jet Black</Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      48 reviews{" "}
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      38 offers
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$499</strong>
                    </Table.Col>
                  </Table.Row>
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/gopro-hero.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>
                      GoPro HERO6 Black <Badge color="default"> New </Badge>
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      66 reviews{" "}
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      47 offers
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$599</strong>
                    </Table.Col>
                  </Table.Row>
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/msi.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>
                      MSI GV62 i5-7300HQ/8GB/1TB/Win10X GTX1050
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      59 reviews
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      26 offers{" "}
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$1599</strong>
                    </Table.Col>
                  </Table.Row>
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/xiaomi-mi.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>
                      Xiaomi Mi A1 64GB Black<Badge color="default">
                        {" "}
                        New{" "}
                      </Badge>
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      63 reviews
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      43 offers
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$269</strong>
                    </Table.Col>
                  </Table.Row>{" "}
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/huawei-mate.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>Huawei Mate 10 Pro Dual SIM Gray</Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      71 reviews
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      12 offers
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$999</strong>
                    </Table.Col>
                  </Table.Row>
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/sony-kd.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>
                      Sony KD-49XE7005<Badge color="default"> New </Badge>
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      54 reviews
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      14 offers
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$799</strong>
                    </Table.Col>
                  </Table.Row>
                  <Table.Row>
                    <Table.Col>
                      <img
                        alt=""
                        src="https://tabler.github.io/tabler/demo/products/samsung-galaxy.jpg"
                        className="h-8"
                      />
                    </Table.Col>
                    <Table.Col>
                      Samsung Galaxy A5 A520F 2017 LTE Black Sky
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      37 reviews{" "}
                    </Table.Col>
                    <Table.Col className="text-right text-muted d-none d-md-table-cell text-nowrap">
                      {" "}
                      40 offers
                    </Table.Col>
                    <Table.Col className="text-right">
                      <strong>$399</strong>
                    </Table.Col>
                  </Table.Row>
                </Table.Body>
              </Table>
            </Card>
          </Grid.Col>
        </Grid.Row>
      </Page.Content>
    </SiteWrapper>
  );
}

export default StoreCardsPage;
