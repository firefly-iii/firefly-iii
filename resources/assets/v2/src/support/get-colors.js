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

// four other colors:

// base colors for most things (BORDER)
let blue = new Color('#36a2eb');
let red = new Color('#ff6384');
let green = new Color('#4bc0c0');

// four other colors
let orange = new Color('#ff9f40');
let purple = new Color('#9966ff');
let yellow = new Color('#ffcd56');
let grey = new Color('#c9cbcf');

let index = 0;

// or cycle through X colors:

if ('light' === window.theme) {
    // red.lighten(0.3).clearer(0.3);
    // green.lighten(0.3).clearer(0.3);
    // blue.lighten(0.3).clearer(0.3);
    // orange.lighten(0.3).clearer(0.3);
}
if ('dark' === window.theme) {
    red.darken(0.3).desaturate(0.3);
    green.darken(0.3).desaturate(0.3);
    blue.darken(0.3).desaturate(0.3);
    orange.darken(0.3).desaturate(0.3);

}

let allColors = [red, orange, blue, green, purple, yellow, grey, green];

function getColors(type, field) {
    let colors = {
        borderColor: red.rgbString(),
        backgroundColor: red.rgbString(),
    };
    let background;
    switch (type) {
        default:
            let correctedIndex = Math.floor(index / 2);
            let currentIndex = correctedIndex % allColors.length;
            //console.log('index:' + index + ', correctedIndex:' + correctedIndex + ', currentIndex:' + currentIndex);
            background = new Color(allColors[currentIndex].rgbString());
            background.lighten(0.38);

            colors = {
                borderColor: allColors[currentIndex].hexString(),
                backgroundColor: background.hexString(),
            };
            break;
        case 'spent':
            background = new Color(blue.rgbString());
            background.lighten(0.38);
            //console.log('#9ad0f5 vs ' + background.hexString());
            colors = {
                borderColor: blue.rgbString(),
                backgroundColor: background.rgbString(),
            };
            break;
        case 'left':
            background = new Color(green.rgbString());
            background.lighten(0.38);
            colors = {
                borderColor: green.rgbString(),
                backgroundColor: background.rgbString(),
            };
            break;
        case 'budgeted':
            background = new Color(green.rgbString());
            background.lighten(0.38);
            colors = {
                borderColor: green.rgbString(),
                backgroundColor: background.rgbString(),
            };
            break;
        case 'overspent':
            background = new Color(red.rgbString());
            background.lighten(0.22);
            // console.log('#ffb1c1 vs ' + background.hexString());
            colors = {
                borderColor: red.rgbString(),
                backgroundColor: background.rgbString(),
            };
            break;
    }
    index++;

    if ('border' === field) {
        return colors.borderColor;
    }
    if ('background' === field) {
        return colors.backgroundColor;
    }

    return '#FF0000'; // panic!
}


export {getColors};
