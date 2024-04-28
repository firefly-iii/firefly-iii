/*
 * page-navigation.js
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

function logarithmicPaginationLinks(lastPage, matchPage, linkURL) {
    function pageLink(p, page) {
        if(p === page) {
            //  href="'+ linkURL+ p + '"
            return '<li class="page-item active" aria-current="page"><a class="page-link" href="#" @click.prevent="goToPage('+p+')">'+p+'</a></li>';
        }
        //  href="'+ linkURL+ p + '"
        return '<li class="page-item"><a class="page-link" href="#"  @click.prevent="goToPage('+p+')">'+p+'</a></li>';

        // return ((p === page) ? "<b>" + p + "</b>" : '<a href="' + linkURL + p + '">' + p + "</a>");
    }

    let page = (matchPage ? matchPage : 1), LINKS_PER_STEP = 5, lastp1 = 1, lastp2 = page, p1 = 1, p2 = page,
        c1 = LINKS_PER_STEP + 1, c2 = LINKS_PER_STEP + 1, s1 = "", s2 = "", step = 1, linkHTML = "";

    while (true) {
        if (c1 >= c2) {
            s1 += pageLink(p1, matchPage);
            lastp1 = p1;
            p1 += step;
            c1--;
        } else {
            s2 = pageLink(p2, matchPage) + s2;
            lastp2 = p2;
            p2 -= step;
            c2--;
        }
        if (c2 === 0) {
            step *= 25;
            p1 += step - 1;        // Round UP to nearest multiple of step
            p1 -= (p1 % step);
            p2 -= (p2 % step);   // Round DOWN to nearest multiple of step
            c1 = LINKS_PER_STEP;
            c2 = LINKS_PER_STEP;
        }
        if (p1 > p2) {
            linkHTML += s1 + s2;
            if ((lastp2 > page) || (page >= lastPage)) break;
            lastp1 = page;
            lastp2 = lastPage;
            p1 = page + 1;
            p2 = lastPage;
            c1 = LINKS_PER_STEP;
            c2 = LINKS_PER_STEP + 1;
            s1 = '';
            s2 = '';
            step = 1;
        }
    }
    return linkHTML;
}

export default function pageNavigation(totalPages, currentPage, navigationURL) {

    totalPages = parseInt(totalPages);
    currentPage = parseInt(currentPage);
    let html = '<nav aria-label="Page navigation">';
    html += '<ul class="pagination">';
    if(currentPage > 1) {
        html += '<li class="page-item"><a class="page-link" href="#">Previous</a></li>';
    }
    if(1 === currentPage) {
        html += '<li class="page-item disabled"><a class="page-link">Previous</a></li>';
    }
    html += logarithmicPaginationLinks(totalPages, currentPage, navigationURL);
    if(currentPage !== totalPages) {
        html += '<li class="page-item"><a class="page-link" href="#">Next</a></li>';
    }
    if(currentPage === totalPages) {
        html += '<li class="page-item disabled"><a class="page-link">Next</a></li>';
    }
    html += '</ul></nav>';

    return html;
}
