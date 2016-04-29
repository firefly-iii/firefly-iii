/*
 * create.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/* globals what:true, $, doSwitch, txt, middleCrumbName, title,button, middleCrumbUrl, piggiesLength, breadcrumbs */
$(document).ready(function () {
    "use strict";

    // respond to switch buttons when
    // creating stuff:
    if (doSwitch) {
        updateButtons();
        updateForm();
        updateLayout();
    }


});

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
            console.log('Now displaying form for ' + what);
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
    }
    return false;
}