@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-12">
        <h1>Firefly<br/>
            <small>Select a user for migration.</small>
        </h1>
        <p>
            Select a user from the list below. Then press import.
        </p>

        {{Form::open(['class' => 'form-horizontal'])}}

        <div class="form-group">
            <label for="inputUser" class="col-sm-2 control-label">User</label>
            <div class="col-sm-10">
                <select class="form-control" name="user">
                    @foreach($oldUsers as $old)
                    <option value="{{$old->id}}" label="# {{$old->id}}: {{$old->username}} ({{$old->email}})">
                        # {{$old->id}}: {{$old->username}} ({{$old->email}})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-info">Import</button><br />
                <small>Please be patient; importing data may take some time.</small>
            </div>
        </div>
        {{Form::close()}}
    </div>
</div>

@stop