// @flow

import * as React from "react";

import { Page, Grid, PricingCard } from "tabler-react";

import SiteWrapper from "../SiteWrapper.react";

function PricingCardsPage(): React.Node {
  return (
    <SiteWrapper>
      <Page.Content title="Pricing cards">
        <Grid.Row>
          <Grid.Col sm={6} lg={3}>
            <PricingCard>
              <PricingCard.Category>{"Free"}</PricingCard.Category>
              <PricingCard.Price>{"$0"} </PricingCard.Price>
              <PricingCard.AttributeList>
                <PricingCard.AttributeItem>
                  <strong>3 </strong>
                  {"Users"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Sharing Tools"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available={false}>
                  {"Design Tools"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available={false}>
                  {"Private Messages"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available={false}>
                  {"Twitter API"}
                </PricingCard.AttributeItem>
              </PricingCard.AttributeList>
              <PricingCard.Button> {"Choose plan"} </PricingCard.Button>
            </PricingCard>
          </Grid.Col>

          <Grid.Col sm={6} lg={3}>
            <PricingCard active>
              <PricingCard.Category>{"Premium"}</PricingCard.Category>
              <PricingCard.Price>{"$49"} </PricingCard.Price>
              <PricingCard.AttributeList>
                <PricingCard.AttributeItem>
                  <strong>10 </strong>
                  {"Users"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Sharing Tools"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Design Tools"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available={false}>
                  {"Private Messages"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available={false}>
                  {"Twitter API"}
                </PricingCard.AttributeItem>
              </PricingCard.AttributeList>
              <PricingCard.Button active>{"Choose plan"} </PricingCard.Button>
            </PricingCard>
          </Grid.Col>

          <Grid.Col sm={6} lg={3}>
            <PricingCard>
              <PricingCard.Category>{"Enterprise"}</PricingCard.Category>
              <PricingCard.Price>{"$99"} </PricingCard.Price>
              <PricingCard.AttributeList>
                <PricingCard.AttributeItem>
                  <strong>100 </strong>
                  {"Users"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Sharing Tools"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Design Tools"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Private Messages"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available={false}>
                  {"Twitter API"}
                </PricingCard.AttributeItem>
              </PricingCard.AttributeList>
              <PricingCard.Button>{"Choose plan"} </PricingCard.Button>
            </PricingCard>
          </Grid.Col>

          <Grid.Col sm={6} lg={3}>
            <PricingCard>
              <PricingCard.Category>{"Unlimited"}</PricingCard.Category>
              <PricingCard.Price>{"$139"} </PricingCard.Price>
              <PricingCard.AttributeList>
                <PricingCard.AttributeItem>
                  <strong>Unlimited </strong>
                  {"Users"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Sharing Tools"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Design Tools"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Private Messages"}
                </PricingCard.AttributeItem>
                <PricingCard.AttributeItem hasIcon available>
                  {"Twitter API"}
                </PricingCard.AttributeItem>
              </PricingCard.AttributeList>
              <PricingCard.Button>{"Choose plan"} </PricingCard.Button>
            </PricingCard>
          </Grid.Col>
        </Grid.Row>
      </Page.Content>
    </SiteWrapper>
  );
}

export default PricingCardsPage;
