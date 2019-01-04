// @flow

import React from "react";

import {
  Container,
  Grid,
  Card,
  Button,
  Form,
  Avatar,
  Profile,
  List,
  Media,
  Text,
  Comment,
} from "tabler-react";

import SiteWrapper from "../SiteWrapper.react";

function ProfilePage() {
  return (
    <SiteWrapper>
      <div className="my-3 my-md-5">
        <Container>
          <Grid.Row>
            <Grid.Col lg={4}>
              <Profile
                name="Peter Richards"
                backgroundURL="demo/photos/eberhard-grossgasteiger-311213-500.jpg"
                avatarURL="demo/faces/male/16.jpg"
                twitterURL="test"
              >
                Big belly rude boy, million dollar hustler. Unemployed.
              </Profile>
              <Card>
                <Card.Body>
                  <Media>
                    <Avatar
                      size="xxl"
                      className="mr-5"
                      imageURL="demo/faces/male/21.jpg"
                    />
                    <Media.BodySocial
                      name="Juan Hernandez"
                      workTitle="Webdeveloper"
                      facebook="Facebook"
                      twitter="Twitter"
                      phone="1234567890"
                      skype="@skypename"
                    />
                  </Media>
                </Card.Body>
              </Card>
              <Card>
                <Card.Header>
                  <Card.Title>My Profile</Card.Title>
                </Card.Header>
                <Card.Body>
                  <Form>
                    <Grid.Row>
                      <Grid.Col auto>
                        <Avatar size="xl" imageURL="demo/faces/female/9.jpg" />
                      </Grid.Col>
                      <Grid.Col>
                        <Form.Group>
                          <Form.Label>Email-Address</Form.Label>
                          <Form.Input placeholder="your-email@domain.com" />
                        </Form.Group>
                      </Grid.Col>
                    </Grid.Row>
                    <Form.Group>
                      <Form.Label>Bio</Form.Label>
                      <Form.Textarea rows={5}>
                        Big belly rude boy, million dollar hustler. Unemployed.
                      </Form.Textarea>
                    </Form.Group>
                    <Form.Group>
                      <Form.Label>Email-Address</Form.Label>
                      <Form.Input placeholder="your-email@domain.com" />
                    </Form.Group>
                    <Form.Group>
                      <Form.Label>Password</Form.Label>
                      <Form.Input type="password" value="Password" />
                    </Form.Group>
                    <Form.Footer>
                      <Button color="primary" block>
                        Save
                      </Button>
                    </Form.Footer>
                  </Form>
                </Card.Body>
              </Card>
            </Grid.Col>
            <Grid.Col lg={8}>
              <Card>
                <Card.Header>
                  <Form.InputGroup>
                    <Form.Input type="text" placeholder="Message" />
                    <Form.InputGroup append>
                      <Button icon="camera" color="secondary" />
                    </Form.InputGroup>
                  </Form.InputGroup>
                </Card.Header>
                <Comment.List>
                  <Comment
                    avatarURL="demo/faces/male/16.jpg"
                    name="Peter Richards"
                    date="4 min"
                    text="Aenean lacinia bibendum nulla sed consectetur. Vestibulum id ligula porta felis euismod semper. Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Vestibulum id ligula porta felis euismod semper. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus."
                    replies={
                      <React.Fragment>
                        <Comment.Reply
                          name="Debra Beck"
                          avatarURL="demo/faces/female/17.jpg"
                          text="Donec id elit non mi porta gravida at eget metus. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Donec ullamcorper nulla non metus auctor fringilla. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Sed posuere consectetur est at lobortis."
                        />
                        <Comment.Reply
                          name="Jack Ruiz"
                          avatarURL="demo/faces/male/32.jpg"
                          text="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus."
                        />
                      </React.Fragment>
                    }
                  />
                  <Comment
                    avatarURL="demo/faces/male/16.jpg"
                    date="12 min"
                    name="Peter Richards"
                    text="Donec id elit non mi porta gravida at eget metus. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Donec ullamcorper nulla non metus auctor fringilla. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Sed posuere consectetur est at lobortis."
                  />
                  <Comment
                    avatarURL="demo/faces/male/16.jpg"
                    date="34 min"
                    name="Peter Richards"
                    text="Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper. Aenean eu leo quam. Pellentesque ornare sem lacinia quam venenatis vestibulum. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui."
                    replies={
                      <Comment.Reply
                        name="Wayne Holland"
                        avatarURL="demo/faces/male/26.jpg"
                        text=" Donec id elit non mi porta gravida at eget metus. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Donec ullamcorper nulla non metus auctor fringilla. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Sed posuere consectetur est at lobortis."
                      />
                    }
                  />
                </Comment.List>
              </Card>
              <Form className="card">
                <Card.Body>
                  <Card.Title>Edit Profile</Card.Title>
                  <Grid.Row>
                    <Grid.Col md={5}>
                      <Form.Group>
                        <Form.Label>Company</Form.Label>
                        <Form.Input
                          type="text"
                          disabled
                          placeholder="Company"
                          value="Creative Code Inc."
                        />
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col sm={6} md={3}>
                      <Form.Group>
                        <Form.Label>Username</Form.Label>
                        <Form.Input
                          type="text"
                          placeholder="Username"
                          value="michael23"
                        />
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col sm={6} md={4}>
                      <Form.Group>
                        <Form.Label>Email address</Form.Label>
                        <Form.Input type="email" placeholder="Email" />
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col sm={6} md={6}>
                      <Form.Group>
                        <Form.Label>First Name</Form.Label>
                        <Form.Input
                          type="text"
                          placeholder="First Name"
                          value="Chet"
                        />
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col sm={6} md={6}>
                      <Form.Group>
                        <Form.Label>Last Name</Form.Label>
                        <Form.Input
                          type="text"
                          placeholder="Last Name"
                          value="Faker"
                        />
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col md={12}>
                      <Form.Group>
                        <Form.Label>Address</Form.Label>
                        <Form.Input
                          type="text"
                          placeholder="Home Address"
                          value="Melbourne, Australia"
                        />
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col sm={6} md={4}>
                      <Form.Group>
                        <Form.Label>City</Form.Label>
                        <Form.Input
                          type="text"
                          placeholder="City"
                          value="Melbourne"
                        />
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col sm={6} md={3}>
                      <Form.Group>
                        <Form.Label>Postal Code</Form.Label>
                        <Form.Input type="number" placeholder="ZIP Code" />
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col md={5}>
                      <Form.Group>
                        <Form.Label>Country</Form.Label>
                        <Form.Select>
                          <option>Germany</option>
                        </Form.Select>
                      </Form.Group>
                    </Grid.Col>
                    <Grid.Col md={12}>
                      <Form.Group className="mb=0" label="About Me">
                        <Form.Textarea
                          rows={5}
                          placeholder="Here can be your description"
                        >
                          Oh so, your weak rhyme You doubt I'll bother, reading
                          into it I'll probably won't, left to my own devices
                          But that's the difference in our opinions.
                        </Form.Textarea>
                      </Form.Group>
                    </Grid.Col>
                  </Grid.Row>
                </Card.Body>
                <Card.Footer className="text-right">
                  <Button type="submit" color="primary">
                    Update Profile
                  </Button>
                </Card.Footer>
              </Form>
            </Grid.Col>
          </Grid.Row>
        </Container>
      </div>
    </SiteWrapper>
  );
}

export default ProfilePage;
