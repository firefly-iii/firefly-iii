<form style="display: inline;" id="remove" action="{{route('piggy_banks.remove',$piggyBank->id)}}" method="POST">
{{Form::token()}}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">Remove money from {{{$piggyBank->name}}}</h4>
            </div>
            <div class="modal-body">
                <p>
                    The maximum amount you can remove is {{mf($piggyBank->currentRelevantRep()->currentamount)}}
                </p>
                <div class="input-group">
                    <div class="input-group-addon">€</div>
                    <input step="any" class="form-control" id="amount" autocomplete="off" name="amount" max="{{round($piggyBank->currentRelevantRep()->currentamount,2)}}" type="number">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Remove</button>
            </div>
        </div>
    </div>
</form>
