<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
    <h4 class="modal-title" id="myModalLabel">
        @if($what == 'add')
            Add money to "{{{$piggybank->name}}}"
        @else
            Remove money from "{{{$piggybank->name}}}"
        @endif
        </h4>
</div>
<form style="display: inline;" action="{{route('piggybanks.modMoney',$piggybank->id)}}" method="POST">
    <input type="hidden" name="what" value="{{$what}}" />
    {{Form::token()}}
    <div class="modal-body">
        <p class="text-info">
            @if($what == 'add')
            Usually you would add money to this
            @if($piggybank->repeated == 1)
                repeated expense
            @else
                piggy bank
            @endif
            by transferring it from one of your accounts to "{{{$piggybank->account->name}}}". However,
            since there is still {{mf($maxAdd)}} you can add manually.

            @else

            If you need the money in this
            @if($piggybank->repeated == 1)
                repeated expense
            @else
                piggy bank
            @endif
            for something else, you can opt to remove it using this form. Since there is {{mf($maxRemove)}} in this
            @if($piggybank->repeated == 1)
                repeated expense
            @else
                piggy bank
            @endif
            that is the maximum amount of money you can remove using this form.
            @endif
        </p>
        <div class="form-group">
            <label for="amount">Amount to {{$what}}</label>
            <div class="input-group">
                <div class="input-group-addon">&euro;</div>
            @if($what == 'add')
                <input type="number" step="any" max="{{round(min($maxAdd,$piggybank->targetamount),2)}}" min="0.01" class="form-control" id="amount" name="amount">
            @else
                <input type="number" step="any" max="{{round($maxRemove,2)}}" min="0.01" class="form-control" id="amount" name="amount">
            @endif
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <input type="submit" class="btn btn-primary" value="Submit" name="submit" />
    </div>
</form>

