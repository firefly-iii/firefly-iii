/*
 * create.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: what, title, breadcrumbs, middleCrumbName, button, piggiesLength, txt, doSwitch, middleCrumbUrl */

$(document).ready(function () {
    "use strict";

    // respond to switch buttons when
    // creating stuff:
    if (doSwitch == true) {
        updateButtons();
        updateForm();
        updateLayout();
        updateDescription();
    }

    // get JSON things:
    getJSONautocomplete();

});

function updateDescription() {
    $.getJSON('json/transaction-journals/' + what).done(function (data) {
        $('input[name="description"]').typeahead('destroy');
        $('input[name="description"]').typeahead({source: data});
    });
}

function getJSONautocomplete() {

    // for withdrawals
    $.getJSON('json/expense-accounts').done(function (data) {
        $('input[name="destination_account_name"]').typeahead({source: data});
    });

    // for tags:
    if ($('input[name="tags"]').length > 0) {
        $.getJSON('json/tags').done(function (data) {

            var opt = {
                typeahead: {
                    source: data,
                    afterSelect: function () {
                        this.$element.val("");
                    }
                }
            };
            $('input[name="tags"]').tagsinput(
                opt
            );
        });
    }

    // for deposits
    $.getJSON('json/revenue-accounts').done(function (data) {
        $('input[name="source_account_name"]').typeahead({source: data});
    });

    $.getJSON('json/categories').done(function (data) {
        $('input[name="category"]').typeahead({source: data});
    });

}

function updateLayout() {
    "use strict";
    $('#subTitle').text(title[what]);
    $('.breadcrumb .active').text(breadcrumbs[what]);
    $('.breadcrumb li:nth-child(2)').html('<a href="' + middleCrumbUrl[what] + '">' + middleCrumbName[what] + '</a>');
    $('#transaction-btn').text(button[what]);
}

function updateForm() {
    "use strict";

    $('input[name="what"]').val(what);
    switch (what) {
        case 'withdrawal':
            // show source_id and dest_name:
            $('#source_account_id_holder').show();
            $('#destination_account_name_holder').show();

            // hide others:
            $('#source_account_name_holder').hide();
            $('#destination_account_id_holder').hide();

            // show budget:
            $('#budget_id_holder').show();

            // hide piggy bank:
            $('#piggy_bank_id_holder').hide();

            // copy destination account name to
            // source account name:
            if ($('#ffInput_destination_account_name').val().length > 0) {
                $('#ffInput_source_account_name').val($('#ffInput_destination_account_name').val());
            }

            break;
        case 'deposit':
            // show source_name and dest_id:
            $('#source_account_name_holder').show();
            $('#destination_account_id_holder').show();

            // hide others:
            $('#source_account_id_holder').hide();
            $('#destination_account_name_holder').hide();

            // hide budget
            $('#budget_id_holder').hide();

            // hide piggy bank
            $('#piggy_bank_id_holder').hide();

            if ($('#ffInput_source_account_name').val().length > 0) {
                $('#ffInput_destination_account_name').val($('#ffInput_source_account_name').val());
            }

            break;
        case 'transfer':
            // show source_id and dest_id:
            $('#source_account_id_holder').show();
            $('#destination_account_id_holder').show();

            // hide others:
            $('#source_account_name_holder').hide();
            $('#destination_account_name_holder').hide();


            // hide budget
            $('#budget_id_holder').hide();
            if (piggiesLength === 0) {
                $('#piggy_bank_id_holder').hide();
            } else {
                $('#piggy_bank_id_holder').show();
            }
            break;
        default:
            // no action.
            break;
    }
}


function updateButtons() {
    "use strict";
    $('.switch').each(function (i, v) {
        var button = $(v);

        // remove click event:
        button.unbind('click');
        // new click event:
        button.bind('click', clickButton);

        if (button.data('what') == what) {
            button.removeClass('btn-default').addClass('btn-info').html('<i class="fa fa-fw fa-check"></i> ' + txt[button.data('what')]);
        } else {
            button.removeClass('btn-info').addClass('btn-default').text(txt[button.data('what')]);
        }
    });
}

function clickButton(e) {
    "use strict";
    var button = $(e.target);
    var newWhat = button.data('what');
    if (newWhat != what) {
        what = newWhat;
        updateButtons();
        updateForm();
        updateLayout();
        updateDescription();
    }
    return false;
}