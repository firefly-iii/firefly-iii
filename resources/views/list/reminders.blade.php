<div class="row">
@foreach($reminders as $reminder)
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                Reminder for piggy bank "{{$reminder->remindersable->name}}"
            </div>
            <div class="panel-body">
                @if(isset($reminder->description))
                    {!! $reminder->description !!}
                @endif
            </div>
            <div class="panel-footer">
                <div class="btn-group">
                    <a class="btn btn-warning" href="#">Dismiss</a>
                    <a class="btn btn-success" href="#">Act</a>
                </div>
            </div>
        </div>
    </div>

@endforeach
</div>