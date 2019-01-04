// @flow

import * as React from "react";

import { Page, Grid, GalleryCard, Form } from "tabler-react";

import SiteWrapper from "./SiteWrapper.react";

import json from "./data/Gallery.Items";
// TODO:Add GalleryCardList component to avoid insert extra className
// TODO:Update Page.Header to additional components

function GalleryPage(): React.Node {
  const options = (
    <React.Fragment>
      <Form.Select className="w-auto mr-2">
        <option value="asc">Newest</option>
        <option value="desc">Oldest</option>
      </Form.Select>
      <Form.Input icon="search" placeholder="Search photo" />
    </React.Fragment>
  );
  return (
    <SiteWrapper>
      <Page.Content>
        <Page.Header
          title="Gallery"
          subTitle="1 - 12 of 1713 photos"
          options={options}
        />

        <Grid.Row className="row-cards">
          {json.items.map((item, key) => (
            <Grid.Col sm={6} lg={4} key={key}>
              <GalleryCard>
                <GalleryCard.Image
                  src={item.imageURL}
                  alt={`Photo by ${item.fullName}`}
                />
                <GalleryCard.Footer>
                  <GalleryCard.Details
                    avatarURL={item.avatarURL}
                    fullName={item.fullName}
                    dateString={item.dateString}
                  />
                  <GalleryCard.IconGroup>
                    <GalleryCard.IconItem name="eye" label={item.totalView} />
                    <GalleryCard.IconItem
                      name="heart"
                      label={item.totalLike}
                      right
                    />
                  </GalleryCard.IconGroup>
                </GalleryCard.Footer>
              </GalleryCard>
            </Grid.Col>
          ))}
        </Grid.Row>
      </Page.Content>
    </SiteWrapper>
  );
}

export default GalleryPage;
