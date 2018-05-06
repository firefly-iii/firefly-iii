$(function () {
    "use strict";

    var importMultiSelect = {
        disableIfEmpty: true,
        selectAllText: selectAllText,
        nonSelectedText: nonSelectedText,
        nSelectedText: nSelectedText,
        allSelectedText: allSelectedText,
        includeSelectAllOption: true,
        enableFiltering: true,
        enableCaseInsensitiveFiltering: true,
        filterPlaceholder: filterPlaceholder,
        enableHTML: true,
    };

// make account select a hip new bootstrap multi-select thing.
    $('#inputSpecifics').multiselect(importMultiSelect);

});