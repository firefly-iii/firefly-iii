/*
 * get-colors.js
 * Copyright (c) 2023 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// some default colors in dark and light:


import {Color} from '@kurkle/color';

// base colors for most things
let red = new Color('#dc3545'); // same as bootstrap danger
let green = new Color('#198754'); // same as bootstrap success.
let blue = new Color('#0d6efd'); // bootstrap blue.

// four other colors:
let orange = new Color('#fd7e14'); // bootstrap orange.


let index = 0;

// or cycle through X colors:

if ('light' === window.theme) {
    red.lighten(0.3).clearer(0.3);
    green.lighten(0.3).clearer(0.3);
    blue.lighten(0.3).clearer(0.3);
    orange.lighten(0.3).clearer(0.3);
}


let allColors = [red, green, blue, orange];

function getColors(type, field) {
    index++;
    let colors = {
        borderColor: red.rgbString(),
        backgroundColor: red.rgbString(),
    };
    let border;
    switch (type) {
        default:
            let currentIndex = (Math.ceil(index / 2) % allColors.length) - 1;
            border = new Color(allColors[currentIndex].rgbString());
            border.darken(0.4);
            colors = {
                borderColor: border.rgbString(),
                backgroundColor: allColors[currentIndex].rgbString(),
            };
            break;
        case 'spent':
            border = new Color(blue.rgbString());
            border.darken(0.4);
            colors = {
                borderColor: border.rgbString(),
                backgroundColor: blue.rgbString(),
            };
            break;
        case 'left':
            border = new Color(green.rgbString());
            border.darken(0.4);
            colors = {
                borderColor: border.rgbString(),
                backgroundColor: green.rgbString(),
            };
            break;
        case 'overspent':
            border = new Color(red.rgbString());
            border.darken(0.4);
            colors = {
                borderColor: border.rgbString(),
                backgroundColor: red.rgbString(),
            };
            break;
    }

    if ('border' === field) {
        return colors.borderColor;
    }
    if ('background' === field) {
        return colors.backgroundColor;
    }

    return '#FF0000'; // panic!
}


export {getColors};
