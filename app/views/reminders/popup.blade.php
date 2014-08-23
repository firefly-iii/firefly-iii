<div class="modal-header">
    <button type="button" class="close"
            data-dismiss="modal"><span aria-hidden="true">&times;</span>
        <span class="sr-only">Close</span></button>
    <h4 class="modal-title">Reminders</h4>
</div>
<div class="modal-body">
    <table class="table">
    @foreach($reminders as $reminder)
    <tr class="reminder-row-{{$reminder->id}}">
        <td>{{$reminder->render()}}</td>
        <td style="width:50%;">
                <a href="#" data-id="{{$reminder->id}}" class="dismiss-24 btn btn-danger btn-sm">Postpone (24hrs)</a>
                <a href="#" data-id="{{$reminder->id}}" class="dismiss-forever btn btn-danger btn-sm">Dismiss (forever)</a>
                <a href="#" data-id="{{$reminder->id}}" class="do-it btn btn-success btn-sm">I want to do this</a>
        </td>
    </tr>

    @endforeach
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>