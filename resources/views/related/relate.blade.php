<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            <h4 class="modal-title">Relate "{{{$journal->description}}}" to other transactions</h4>
        </div>
        <div class="modal-body">
            <p>
                Use this form to relate this transaction to other transactions. An often used relation is any unexpected expenses and the balancing transfer
                from a savings account.
            </p>
            <form class="form-inline" role="form" id="searchRelated">
                <div class="form-group">
                    <input type="text" style="width:400px;" class="form-control" name="related" id="relatedSearchValue" placeholder="Search for related transactions">
                </div>
              <button type="submit" class="btn btn-default">Search</button>
            </form>
            <h5 id="relatedSearchResultsTitle" style="display:none;">Search results</h5>
            <div id="relatedSearchResults">
            </div>
            <h5>(Already) related transactions</h5>
            <div>
                <table id="alreadyRelated" class="table table-bordered table-striped"></table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
