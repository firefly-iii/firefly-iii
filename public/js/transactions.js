/* globals what:true, $, doSwitch, txt, middleCrumbName, title,button, middleCrumbUrl, piggiesLength, breadcrumbs */
$(document).ready(function () {
    "use strict";
    if ($('input[name="expense_account"]').length > 0) {
        $.getJSON('json/expense-accounts').success(function (data) {
            $('input[name="expense_account"]').typeahead({source: data});
        });
    }

    if ($('input[name="tags"]').length > 0) {
        $.getJSON('json/tags').success(function (data) {
            var opt = {
                typeahead: {
                    source: data
                }
            };
            $('input[name="tags"]').tagsinput(
                opt
            );
        });
    }

    if ($('input[name="revenue_account"]').length > 0) {
        $.getJSON('json/revenue-accounts').success(function (data) {
            $('input[name="revenue_account"]').typeahead({source: data});
        });
    }

    if ($('input[name="description"]').length > 0 && what !== undefined) {
        $.getJSON('json/transaction-journals/' + what).success(function (data) {
            $('input[name="description"]').typeahead({source: data});
        });
    }

    if ($('input[name="category"]').length > 0) {
        $.getJSON('json/categories').success(function (data) {
            $('input[name="category"]').typeahead({source: data});
        });
    }

    // respond to switch buttons:
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
            $('#account_id_holder').show();
            $('#expense_account_holder').show();
            $('#revenue_account_holder').hide();
            $('#account_from_id_holder').hide();
            $('#account_to_id_holder').hide();
            $('#budget_id_holder').show();
            $('#piggy_bank_id_holder').hide();


            if ($('#ffInput_revenue_account').val().length > 0) {
                $('#ffInput_expense_account').val($('#ffInput_revenue_account').val());
            }

            break;
        case 'deposit':
            $('#account_id_holder').show();
            $('#expense_account_holder').hide();
            $('#revenue_account_holder').show();
            $('#account_from_id_holder').hide();
            $('#account_to_id_holder').hide();
            $('#budget_id_holder').hide();
            $('#piggy_bank_id_holder').hide();

            if ($('#ffInput_expense_account').val().length > 0) {
                $('#ffInput_revenue_account').val($('#ffInput_expense_account').val());
            }

            break;
        case 'transfer':
            $('#account_id_holder').hide();
            $('#expense_account_holder').hide();
            $('#revenue_account_holder').hide();
            $('#account_from_id_holder').show();
            $('#account_to_id_holder').show();
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