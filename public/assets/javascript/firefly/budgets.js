$(function () {
    updateRanges();
    $('input[type="range"]').change(updateSingleRange);
    $('input[type="range"]').on('input', updateSingleRange);
    $('input[type="number"]').on('change', updateSingleRange);
    $('input[type="number"]').on('input', updateSingleRange);
    $('.updateIncome').on('click', updateIncome);


    if (typeof(googleTable) == 'function') {
        if (typeof componentID != 'undefined' && typeof repetitionID == 'undefined') {
            googleTable('table/component/' + componentID + '/0/transactions', 'transactions');
            googleColumnChart('chart/component/' + componentID + '/spending/' + year, 'componentOverview');

        } else if (typeof componentID != 'undefined' && typeof repetitionID != 'undefined') {
            googleTable('table/component/' + componentID + '/' + repetitionID + '/transactions', 'transactions');
        }
    }

});


function updateSingleRange(e) {
    // get some values:
    var input = $(e.target);
    var id = input.data('id');
    var value = parseInt(input.val());

    var spent = parseFloat($('#spent-' + id).data('value'));
    console.log('Spent vs budgeted: ' + spent + ' vs ' + value)

    // update small display:
    if (value > 0) {
        // show the input:
        $('#budget-info-' + id + ' span').show();
        $('#budget-info-' + id + ' input').show();

        // update the text:
        $('#budget-description-' + id).text('Budgeted: ');

        //if(value < spent) {
        //    $('#budgeted-' + id).html('Budgeted: <span class="text-danger">\u20AC ' + value.toFixed(2) + '</span>');
        //} else {
        //    $('#budgeted-' + id).html('Budgeted: <span class="text-success">\u20AC ' + value.toFixed(2) + '</span>');
        //}
    } else {
        console.log('Set to zero!');
        // hide the input:
        $('#budget-info-' + id + ' span').hide();
        $('#budget-info-' + id + ' input').hide();

        // update the text:
        $('#budget-description-' + id).html('<em>No budget</em>');

        //$('#budgeted-' + id).html('<em>No budget</em>');
    }
    // update the range display text:
    $('#budget-range-display-' + id).text('\u20AC ' + value.toFixed(2));

    // send a post to Firefly to update the amount:
    console.log('Value is: ' + value);
    $.post('budgets/amount/' + id, {amount: value}).success(function (data) {
        console.log('Budget ' + data.name + ' updated!');
        // update the link if relevant:
        $('#budget-link-' + id).attr('href', 'budgets/show/' + id + '/' + data.repetition);
    });
    if (input.attr('type') == 'number') {
        // update the range-input:
        $('#budget-range-' + id).val(value);
    } else {
        // update the number-input:
        $('#budget-info-' + id + ' input').val(value);
    }

    // update or hide the bar, whichever is necessary.

    updateTotal();
    return value;
}

function updateTotal() {
    var sum = 0;
    $('input[type="range"]').each(function (i, v) {
        // get some values:
        sum += parseInt($(v).val());
    });

    /**
     * Update total sum:
     */
    var totalAmount = parseInt($('#totalAmount').data('value'));
    if (sum <= totalAmount) {
        var pct = sum / totalAmount * 100;
        $('#progress-bar-default').css('width', pct + '%');
        $('#progress-bar-warning').css('width', '0');
        $('#progress-bar-danger').css('width', '0');
        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-success').removeClass('text-danger');
    } else {
        // we gaan er X overheen,

        var pct = totalAmount / sum * 100;
        console.log(pct)
        var danger = 100 - pct;
        var err = 100 - danger;
        $('#progress-bar-default').css('width', 0);
        $('#progress-bar-warning').css('width', err + '%');
        $('#progress-bar-danger').css('width', danger + '%');
        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-danger').removeClass('text-success');
    }

}

function updateIncome(e) {
    $('#monthlyBudgetModal').empty().load('budgets/income').modal('show');

    return false;
}

function updateRanges() {
    /**
     * Update all ranges.
     */
    var sum = 0;
    $('input[type="range"]').each(function (i, v) {
        // get some values:
        var input = $(v);
        var id = input.data('id');
        var value = parseInt(input.val());

        // calculate sum:
        sum += value

        // update small display:
        $('#budget-range-display-' + id).text('\u20AC ' + value.toFixed(2));

        // update progress bar (if relevant)
        var barHolder = $('#budget-progress-' + id);
        var spent = parseFloat(barHolder.data('spent'));
        if (value > 0 && spent > 0) {
            console.log('Add bar')
            //barHolder.append($('<div class="progress-bar" id="progress-bar-something-' + id + '" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100" style="width: 10%;"></div>'));
        }

        // send a post to Firefly to update the amount:
        $.post('budgets/amount/' + id, {amount: value}).success(function (data) {
            console.log('Budget ' + data.name + ' updated!');
        });

    });

    /**
     * Update total sum:
     */
    var totalAmount = parseInt($('#totalAmount').data('value'));
    if (sum <= totalAmount) {
        var pct = sum / totalAmount * 100;
        $('#progress-bar-default').css('width', pct + '%');
        $('#progress-bar-warning').css('width', '0');
        $('#progress-bar-danger').css('width', '0');
        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-success').removeClass('text-danger');
    } else {
        // we gaan er X overheen,

        var pct = totalAmount / sum * 100;
        var danger = 100 - pct;
        var err = 100 - danger;
        $('#progress-bar-default').css('width', 0);
        $('#progress-bar-warning').css('width', err + '%');
        $('#progress-bar-danger').css('width', danger + '%');
        $('#budgetedAmount').text('\u20AC ' + sum.toFixed(2)).addClass('text-danger').removeClass('text-success');
    }


}