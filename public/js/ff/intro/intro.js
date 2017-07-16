/*
 * intro.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: routeForTour, routeStepsUri, routeForFinishedTour */

$(function () {
    "use strict";
    //alert('show user intro for ' + route_for_tour);
    $.getJSON(routeStepsUri).done(setupIntro)
});

function setupIntro(steps) {

    var intro = introJs();
    intro.setOptions({
                         steps: steps,
                         exitOnEsc: true,
                         exitOnOverlayClick: true,
                         keyboardNavigation: true,
                     });
    intro.oncomplete(reportIntroFinished);
    intro.onexit(reportIntroFinished);
    intro.start();
}

function reportIntroFinished() {
    $.post(routeForFinishedTour);
}