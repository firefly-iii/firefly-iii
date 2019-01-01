/*
 * TopMenu.js
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
import ReactDOM from "react-dom";
import {Nav} from "tabler-react";




class TopMenu extends Component {
    render() {
        return (
            <Nav className="nav nav-tabs border-0 flex-column flex-lg-row">
                <Nav.Item active hasSubNav value="Dashboard" icon="zap">
                    <Nav.SubItem value="Sub Item 1" />
                    <Nav.SubItem>Sub Item 2</Nav.SubItem>
                    <Nav.SubItem icon="globe">Sub Item 3</Nav.SubItem>
                </Nav.Item>
                <Nav.Item to="http://www.example.com">Page Two</Nav.Item>
                <Nav.Item value="Page Three" />
                <Nav.Item active icon="user">
                    Page Four
                </Nav.Item>
            </Nav>
        );
    }
}

if (document.getElementById('TopMenu')) {
    ReactDOM.render(<TopMenu />, document.getElementById('TopMenu'));
}
