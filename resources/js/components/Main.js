/*
 * Main.js
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import "tabler-react/dist/Tabler.css";
import { Card, Button } from "tabler-react";

class MyCard extends Component {
    render() {
        return (
            <Card>
                <Card.Header>
                    <Card.Title>Card Title</Card.Title>
                </Card.Header>
                <Card.Body>
                    <Button color="primary">A Button</Button>
                </Card.Body>
            </Card>
        );
    }
}

/* An example React component */
class Main extends Component {
    render() {
        return (
            <div>
                <h3>All Products</h3>
            </div>
        );
    }
}

export default Main;

/* The if statement is required so as to Render the component on pages that have a div with an ID of "root";
*/

if (document.getElementById('root')) {
    ReactDOM.render(<Main />, document.getElementById('root'));
}

if (document.getElementById('root')) {
    ReactDOM.render(<MyCard />, document.getElementById('myCard'));
}
