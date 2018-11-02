/**
 * adding sorting ability to HTML tables with Bootstrap styling
 * @summary HTML tables sorting ability
 * @version 2.0.0
 * @requires tinysort, moment.js, jQuery
 * @license MIT
 * @author Matus Brlit (drvic10k)
 * @copyright Matus Brlit (drvic10k), bootstrap-sortable contributors
 */

/**
 * TinySort is a small script that sorts HTML elements. It sorts by text- or attribute value, or by that of one of it's children.
 * @summary A nodeElement sorting script.
 * @version 2.2.0
 * @license MIT/GPL
 * @author Ron Valstar <ron@ronvalstar.nl>
 * @copyright Ron Valstar <ron@ronvalstar.nl>
 * @namespace tinysort
 */
!function (a, b) { "use strict"; function c() { return b } "function" == typeof define && define.amd ? define("tinysort", c) : a.tinysort = b }(this, function () { "use strict"; function a(a, f) { function j() { 0 === arguments.length ? s({}) : d(arguments, function (a) { s(c(a) ? { selector: a } : a) }), p = D.length } function s(a) { var b = !!a.selector, c = b && ":" === a.selector[0], d = e(a || {}, r); D.push(e({ hasSelector: b, hasAttr: !(d.attr === i || "" === d.attr), hasData: d.data !== i, hasFilter: c, sortReturnNumber: "asc" === d.order ? 1 : -1 }, d)) } function t() { d(a, function (a, b) { y ? y !== a.parentNode && (E = !1) : y = a.parentNode; var c = D[0], d = c.hasFilter, e = c.selector, f = !e || d && a.matchesSelector(e) || e && a.querySelector(e), g = f ? B : C, h = { elm: a, pos: b, posn: g.length }; A.push(h), g.push(h) }), x = B.slice(0) } function u() { B.sort(v) } function v(a, e) { var f = 0; for (0 !== q && (q = 0) ; 0 === f && p > q;) { var i = D[q], j = i.ignoreDashes ? n : m; if (d(o, function (a) { var b = a.prepare; b && b(i) }), i.sortFunction) f = i.sortFunction(a, e); else if ("rand" == i.order) f = Math.random() < .5 ? 1 : -1; else { var k = h, r = b(a, i), s = b(e, i), t = "" === r || r === g, u = "" === s || s === g; if (r === s) f = 0; else if (i.emptyEnd && (t || u)) f = t && u ? 0 : t ? 1 : -1; else { if (!i.forceStrings) { var v = c(r) ? r && r.match(j) : h, w = c(s) ? s && s.match(j) : h; if (v && w) { var x = r.substr(0, r.length - v[0].length), y = s.substr(0, s.length - w[0].length); x == y && (k = !h, r = l(v[0]), s = l(w[0])) } } f = r === g || s === g ? 0 : s > r ? -1 : r > s ? 1 : 0 } } d(o, function (a) { var b = a.sort; b && (f = b(i, k, r, s, f)) }), f *= i.sortReturnNumber, 0 === f && q++ } return 0 === f && (f = a.pos > e.pos ? 1 : -1), f } function w() { var a = B.length === A.length; E && a ? F ? B.forEach(function (a, b) { a.elm.style.order = b }) : (B.forEach(function (a) { z.appendChild(a.elm) }), y.appendChild(z)) : (B.forEach(function (a) { var b = a.elm, c = k.createElement("div"); a.ghost = c, b.parentNode.insertBefore(c, b) }), B.forEach(function (a, b) { var c = x[b].ghost; c.parentNode.insertBefore(a.elm, c), c.parentNode.removeChild(c) })) } c(a) && (a = k.querySelectorAll(a)), 0 === a.length && console.warn("No elements to sort"); var x, y, z = k.createDocumentFragment(), A = [], B = [], C = [], D = [], E = !0, F = a.length && (f === g || f.useFlex !== !1) && -1 !== getComputedStyle(a[0].parentNode, null).display.indexOf("flex"); return j.apply(i, Array.prototype.slice.call(arguments, 1)), t(), u(), w(), B.map(function (a) { return a.elm }) } function b(a, b) { var d, e = a.elm; return b.selector && (b.hasFilter ? e.matchesSelector(b.selector) || (e = i) : e = e.querySelector(b.selector)), b.hasAttr ? d = e.getAttribute(b.attr) : b.useVal ? d = e.value || e.getAttribute("value") : b.hasData ? d = e.getAttribute("data-" + b.data) : e && (d = e.textContent), c(d) && (b.cases || (d = d.toLowerCase()), d = d.replace(/\s+/g, " ")), d } function c(a) { return "string" == typeof a } function d(a, b) { for (var c, d = a.length, e = d; e--;) c = d - e - 1, b(a[c], c) } function e(a, b, c) { for (var d in b) (c || a[d] === g) && (a[d] = b[d]); return a } function f(a, b, c) { o.push({ prepare: a, sort: b, sortBy: c }) } var g, h = !1, i = null, j = window, k = j.document, l = parseFloat, m = /(-?\d+\.?\d*)\s*$/g, n = /(\d+\.?\d*)\s*$/g, o = [], p = 0, q = 0, r = { selector: i, order: "asc", attr: i, data: i, useVal: h, place: "start", returns: h, cases: h, forceStrings: h, ignoreDashes: h, sortFunction: i, useFlex: h, emptyEnd: h }; return j.Element && function (a) { a.matchesSelector = a.matchesSelector || a.mozMatchesSelector || a.msMatchesSelector || a.oMatchesSelector || a.webkitMatchesSelector || function (a) { for (var b = this, c = (b.parentNode || b.document).querySelectorAll(a), d = -1; c[++d] && c[d] != b;); return !!c[d] } }(Element.prototype), e(f, { loop: d }), e(a, { plugin: f, defaults: r }) }());

(function ($) {

    var $document = $(document),
        signClass,
        sortEngine,
        emptyEnd;

    $.bootstrapSortable = function (options) {
        if (options == undefined) {
            initialize({});
        }
        else if (options.constructor === Boolean) {
            initialize({applyLast: options});
        }
        else if (options.sortingHeader !== undefined) {
            sortByColumn(options.sortingHeader);
        }
        else {
            initialize(options);
        }
    };

    function initialize(options) {
        // Check if moment.js is available
        var momentJsAvailable = (typeof moment !== 'undefined');

        // Set class based on sign parameter
        signClass = !options.sign ? "arrow" : options.sign;

        // Set sorting algorithm
        if (options.customSort == 'default')
            options.customSort = defaultSortEngine;
        sortEngine = options.customSort || sortEngine || defaultSortEngine;

        emptyEnd = options.emptyEnd;

        // Set attributes needed for sorting
        $('table.sortable').each(function () {
            var $this = $(this);
            var applyLast = (options.applyLast === true);
            $this.find('span.sign').remove();

            // Add placeholder cells for colspans
            $this.find('thead [colspan]').each(function () {
                var colspan = parseFloat($(this).attr('colspan'));
                for (var i = 1; i < colspan; i++) {
                    $(this).after('<th class="colspan-compensate">');
                }
            });

            // Add placeholder cells for rowspans
            $this.find('thead [rowspan]').each(function () {
                var $cell = $(this);
                var rowspan = parseFloat($cell.attr('rowspan'));
                for (var i = 1; i < rowspan; i++) {
                    var parentRow = $cell.parent('tr');
                    var nextRow = parentRow.next('tr');
                    var index = parentRow.children().index($cell);
                    nextRow.children().eq(index).before('<th class="rowspan-compensate">');
                }
            });

            // Set indexes to header cells
            $this.find('thead tr').each(function (rowIndex) {
                $(this).find('th').each(function (columnIndex) {
                    var $header = $(this);
                    $header.addClass('nosort').removeClass('up down');
                    $header.attr('data-sortcolumn', columnIndex);
                    $header.attr('data-sortkey', columnIndex + '-' + rowIndex);
                });
            });

            // Cleanup placeholder cells
            $this.find('thead .rowspan-compensate, .colspan-compensate').remove();

            // Initialize sorting values specified in header
            $this.find('th').each(function () {
                var $header = $(this);
                if ($header.attr('data-dateformat') !== undefined && momentJsAvailable) {
                    var colNumber = parseFloat($header.attr('data-sortcolumn'));
                    $this.find('td:nth-child(' + (colNumber + 1) + ')').each(function () {
                        var $cell = $(this);
                        $cell.attr('data-value', moment($cell.text(), $header.attr('data-dateformat')).format('YYYY/MM/DD/HH/mm/ss'));
                    });
                }
                else if ($header.attr('data-valueprovider') !== undefined) {
                    var colNumber = parseFloat($header.attr('data-sortcolumn'));
                    $this.find('td:nth-child(' + (colNumber + 1) + ')').each(function () {
                        var $cell = $(this);
                        $cell.attr('data-value', new RegExp($header.attr('data-valueprovider')).exec($cell.text())[0]);
                    });
                }
            });

            // Initialize sorting values
            $this.find('td').each(function () {
                var $cell = $(this);
                if ($cell.attr('data-dateformat') !== undefined && momentJsAvailable) {
                    $cell.attr('data-value', moment($cell.text(), $cell.attr('data-dateformat')).format('YYYY/MM/DD/HH/mm/ss'));
                }
                else if ($cell.attr('data-valueprovider') !== undefined) {
                    $cell.attr('data-value', new RegExp($cell.attr('data-valueprovider')).exec($cell.text())[0]);
                }
                else {
                    $cell.attr('data-value') === undefined && $cell.attr('data-value', $cell.text());
                }
            });

            var context = lookupSortContext($this),
                bsSort  = context.bsSort;

            $this.find('thead th[data-defaultsort!="disabled"]').each(function (index) {
                var $header = $(this);
                var $sortTable = $header.closest('table.sortable');
                $header.data('sortTable', $sortTable);
                var sortKey = $header.attr('data-sortkey');
                var thisLastSort = applyLast ? context.lastSort : -1;
                bsSort[sortKey] = applyLast ? bsSort[sortKey] : $header.attr('data-defaultsort');
                if (bsSort[sortKey] !== undefined && (applyLast === (sortKey === thisLastSort))) {
                    bsSort[sortKey] = bsSort[sortKey] === 'asc' ? 'desc' : 'asc';
                    doSort($header, $sortTable);
                }
            });
            $this.trigger('sorted');
        });
    }

    // Add click event to table header
    $document.on('click', 'table.sortable>thead th[data-defaultsort!="disabled"]', function (e) {
        sortByColumn(this);
    });

    // element is the header of the column to sort (the clicked header)
    function sortByColumn(element) {
        var $this = $(element), $table = $this.data('sortTable') || $this.closest('table.sortable');
        $table.trigger('before-sort');
        doSort($this, $table);
        $table.trigger('sorted');
    }

    // Look up sorting data appropriate for the specified table (jQuery element).
    // This allows multiple tables on one page without collisions.
    function lookupSortContext($table) {
        var context = $table.data("bootstrap-sortable-context");
        if (context === undefined) {
            context = {bsSort: [], lastSort: undefined};
            $table.find('thead th[data-defaultsort!="disabled"]').each(function (index) {
                var $this = $(this);
                var sortKey = $this.attr('data-sortkey');
                context.bsSort[sortKey] = $this.attr('data-defaultsort');
                if (context.bsSort[sortKey] !== undefined) {
                    context.lastSort = sortKey;
                }
            });
            $table.data("bootstrap-sortable-context", context);
        }
        return context;
    }

    function defaultSortEngine(rows, sortingParams) {
        tinysort(rows, sortingParams);
    }

    // Sorting mechanism separated
    function doSort($this, $table) {
        var sortColumn = parseFloat($this.attr('data-sortcolumn')),
            context    = lookupSortContext($table),
            bsSort     = context.bsSort;

        var colspan = $this.attr('colspan');
        if (colspan) {
            var mainSort = parseFloat($this.data('mainsort')) || 0;
            var rowIndex = parseFloat($this.data('sortkey').split('-').pop());

            // If there is one more row in header, delve deeper
            if ($table.find('thead tr').length - 1 > rowIndex) {
                doSort($table.find('[data-sortkey="' + (sortColumn + mainSort) + '-' + (rowIndex + 1) + '"]'), $table);
                return;
            }
            // Otherwise, just adjust the sortColumn
            sortColumn = sortColumn + mainSort;
        }

        var localSignClass = $this.attr('data-defaultsign') || signClass;

        // update arrow icon
        $table.find('th').each(function () {
            $(this).removeClass('up').removeClass('down').addClass('nosort');
        });

        if ($.browser.mozilla) {
            var moz_arrow = $table.find('div.mozilla');
            if (moz_arrow !== undefined) {
                moz_arrow.find('.sign').remove();
                moz_arrow.parent().html(moz_arrow.html());
            }
            $this.wrapInner('<div class="mozilla"></div>');
            $this.children().eq(0).append('<span class="sign ' + localSignClass + '"></span>');
        }
        else {
            $table.find('span.sign').remove();
            $this.append('<span class="sign ' + localSignClass + '"></span>');
        }

        // sort direction
        var sortKey = $this.attr('data-sortkey');
        var initialDirection = $this.attr('data-firstsort') !== 'desc' ? 'desc' : 'asc';

        var newDirection = (bsSort[sortKey] || initialDirection);
        if (context.lastSort === sortKey || bsSort[sortKey] === undefined) {
            newDirection = newDirection === 'asc' ? 'desc' : 'asc';
        }
        bsSort[sortKey] = newDirection;
        context.lastSort = sortKey;

        if (bsSort[sortKey] === 'desc') {
            $this.find('span.sign').addClass('up');
            $this.addClass('up').removeClass('down nosort');
        } else {
            $this.addClass('down').removeClass('up nosort');
        }

        // remove rows that should not be sorted
        var rows = $table.children('tbody').children('tr');
        var fixedRows = [];
        $(rows.filter('[data-disablesort="true"]').get().reverse()).each(function (index, fixedRow) {
            var $fixedRow = $(fixedRow);
            fixedRows.push({index: rows.index($fixedRow), row: $fixedRow});
            $fixedRow.remove();
        });

        // sort rows
        var rowsToSort = rows.not('[data-disablesort="true"]');
        if (rowsToSort.length != 0) {
            var emptySorting = bsSort[sortKey] === 'asc' ? emptyEnd : false;
            sortEngine(rowsToSort, {emptyEnd: emptySorting, selector: 'td:nth-child(' + (sortColumn + 1) + ')', order: bsSort[sortKey], data: 'value'});
        }

        // add back the fixed rows
        $(fixedRows.reverse()).each(function (index, row) {
            if (row.index === 0) {
                $table.children('tbody').prepend(row.row);
            } else {
                $table.children('tbody').children('tr').eq(row.index - 1).after(row.row);
            }
        });

        // add class to sorted column cells
        $table.find('td.sorted, th.sorted').removeClass('sorted');
        rowsToSort.find('td:eq(' + sortColumn + ')').addClass('sorted');
        $this.addClass('sorted');
    }

    // jQuery 1.9 removed this object
    if (!$.browser) {
        $.browser = {chrome: false, mozilla: false, opera: false, msie: false, safari: false};
        var ua = navigator.userAgent;
        $.each($.browser, function (c) {
            $.browser[c] = ((new RegExp(c, 'i').test(ua))) ? true : false;
            if ($.browser.mozilla && c === 'mozilla') {
                $.browser.mozilla = ((new RegExp('firefox', 'i').test(ua))) ? true : false;
            }
            if ($.browser.chrome && c === 'safari') {
                $.browser.safari = false;
            }
        });
    }

    // Initialise on DOM ready
    $($.bootstrapSortable);

}(jQuery));
