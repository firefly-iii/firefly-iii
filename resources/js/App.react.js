import * as React from "react";
import { BrowserRouter as Router, Route, Switch } from "react-router-dom";
import {
    ForgotPasswordPage,
    LoginPage,
    RegisterPage,
    Error400,
    Error401,
    Error403,
    Error404,
    Error500,
    Error503,
    Empty,
    Email,
    ProfilePage,
} from "./pages";

import HomePage from "./HomePage.react";
import FormElementsPage from "./FormElementsPage.react";
import PricingCardsPage from "./interface/PricingCardsPage.react";
import CardsDesignPage from "./interface/CardsDesignPage.react";
import StoreCardsPage from "./components/StoreCardsPage.react.js";
import IconPage from "./components/IconPage.react.js";
import ChartsPage from "./interface/ChartsPage.react";
import GalleryPage from "./GalleryPage.react";
import MapCardsPage from "./components/MapCardsPage.react";
import BlogPage from "./components/BlogPage.react";

import "tabler-react/dist/Tabler.css";

type Props = {||};

function App(props: Props): React.Node {
    return (
        <React.StrictMode>
            <Router>
                <Switch>
                    <Route exact path="/" component={HomePage} />
                    <Route exact path="/400" component={Error400} />
                    <Route exact path="/401" component={Error401} />
                    <Route exact path="/403" component={Error403} />
                    <Route exact path="/404" component={Error404} />
                    <Route exact path="/500" component={Error500} />
                    <Route exact path="/503" component={Error503} />
                    <Route exact path="/blog" component={BlogPage} />
                    <Route exact path="/cards" component={CardsDesignPage} />
                    <Route exact path="/charts" component={ChartsPage} />
                    <Route exact path="/email" component={Email} />
                    <Route exact path="/empty-page" component={Empty} />
                    <Route exact path="/form-elements" component={FormElementsPage} />
                    <Route exact path="/forgot-password" component={ForgotPasswordPage} />
                    <Route exact path="/gallery" component={GalleryPage} />
                    <Route exact path="/icons" component={IconPage} />
                    <Route exact path="/login" component={LoginPage} />
                    <Route exact path="/maps" component={MapCardsPage} />
                    <Route exact path="/pricing-cards" component={PricingCardsPage} />
                    <Route exact path="/profile" component={ProfilePage} />
                    <Route exact path="/register" component={RegisterPage} />
                    <Route exact path="/store" component={StoreCardsPage} />
                    <Route component={Error404} />
                </Switch>
            </Router>
        </React.StrictMode>
    );
}

export default App;
