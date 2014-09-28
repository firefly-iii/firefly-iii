@extends('layouts.default')
@section('content')
{{Form::open(['class' => 'form-horizontal','url' => route('recurring.store')])}}

<div class="row">
    <div class="col-lg-6 col-md-12 col-sm-6">
        <!-- panel for mandatory fields -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="fa fa-exclamation-circle"></i> Mandatory fields
            </div>
            <div class="panel-body">
                <!-- name -->
                <div class="form-group">
                    <label for="name" class="col-sm-4 control-label">Name</label>
                    <div class="col-sm-8">
                        <input type="text" name="name" class="form-control" id="name" value="{{Input::old('name')}}" placeholder="Name">
                        @if($errors->has('name'))
                        <p class="text-danger">{{$errors->first('name')}}</p>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <label for="match" class="col-sm-4 control-label">Matches on</label>
                    <div class="col-sm-8">
                        <input type="text" name="match" class="form-control" id="match" value="{{Input::old('match')}}" data-role="tagsinput">
                        @if($errors->has('match'))
                        <p class="text-danger">{{$errors->first('match')}}</p>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    {{ Form::label('amount_min', 'Minimum amount', ['class' => 'col-sm-4 control-label'])}}
                    <div class="col-sm-8">
                        <div class="input-group">
                            <span class="input-group-addon">&euro;</span>
                            {{Form::input('number','amount_min', Input::old('amount_min'), ['step' => 'any', 'class' => 'form-control'])}}
                        </div>

                        @if($errors->has('amount_min'))
                            <p class="text-danger">{{$errors->first('amount_min')}}</p>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    {{ Form::label('amount_max', 'Maximum amount', ['class' => 'col-sm-4 control-label'])}}
                    <div class="col-sm-8">
                        <div class="input-group">
                            <span class="input-group-addon">&euro;</span>
                            {{Form::input('number','amount_max', Input::old('amount_max'), ['step' => 'any', 'class' => 'form-control'])}}
                        </div>

                        @if($errors->has('amount_max'))
                        <p class="text-danger">{{$errors->first('amount_max')}}</p>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    {{ Form::label('date', 'Date', ['class' => 'col-sm-4 control-label'])}}
                    <div class="col-sm-8">
                        {{ Form::input('date','date', Input::old('date') ?: Carbon\Carbon::now()->addDay()->format('Y-m-d'), ['class'
                        => 'form-control']) }}
                        @if($errors->has('date'))
                        <p class="text-danger">{{$errors->first('date')}}</p>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label for="period" class="col-sm-4 control-label">Recurrence</label>
                    <div class="col-sm-8">
                        {{Form::select('repeat_freq',$periods,Input::old('repeat_freq') ?: 'monthly',['class' => 'form-control'])}}
                        @if($errors->has('repeat_freq'))
                        <p class="text-danger">{{$errors->first('repeat_freq')}}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-lg btn-success">
                <i class="fa fa-plus-circle"></i> Store new recurring transaction
            </button>
        </p>
    </div>
    <div class="col-lg-6 col-md-12 col-sm-6">
        <!-- panel for optional fields -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-smile-o"></i> Optional fields
            </div>
            <div class="panel-body">
                <div class="form-group">
                    {{ Form::label('skip', 'Skip', ['class' => 'col-sm-4 control-label'])}}
                    <div class="col-sm-8">
                        {{Form::input('number','skip', Input::old('skip') ?: 0, ['class' => 'form-control'])}}

                        @if($errors->has('skip'))
                        <p class="text-danger">{{$errors->first('skip')}}</p>
                        @else
                        <span class="help-block">Make Firefly skip every <em>n</em> times. Fill in <code>2</code>, and Firefly
                        will match, skip, skip and match a transaction.</span>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <label for="automatch" class="col-sm-4 control-label">Auto-match</label>
                    <div class="col-sm-8">
                        <div class="checkbox">
                            <label>
                                {{Form::checkbox('automatch',1,Input::old('automatch') == '1' || !Input::old('automatch'))}}
                                Yes
                            </label>
                        </div>
                        <span class="help-block">Firefly will automatically match transactions.</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="active" class="col-sm-4 control-label">Active</label>
                    <div class="col-sm-8">
                        <div class="checkbox">
                            <label>
                                {{Form::checkbox('active',1,Input::old('active') == '1' || !Input::old('active'))}}
                                Yes
                            </label>
                        </div>
                        <span class="help-block">This recurring transaction is actually active.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- panel for options -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bolt"></i> Options
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label for="default" class="col-sm-4 control-label">
                    Store
                    </label>
                    <div class="col-sm-8">
                        <div class="radio">
                        <label>
                            {{Form::radio('post_submit_action','store',true)}}
                            Store the recurring transaction
                        </label>
                    </div>
                </div>
            </div>
                <div class="form-group">
                    <label for="validate_only" class="col-sm-4 control-label">
                    Validate only
                    </label>
                    <div class="col-sm-8">
                        <div class="radio">
                        <label>
                            {{Form::radio('post_submit_action','validate_only')}}
                            Only validate, do not save
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="return_to_form" class="col-sm-4 control-label">
                Return here
                </label>
                <div class="col-sm-8">
                    <div class="radio">
                    <label>
                        {{Form::radio('post_submit_action','create_another')}}
                        After storing, return here to create another one.
                    </label>
                </div>
            </div>
        </div>
        </div>
    </div>

    </div>
</div>

{{Form::close()}}


@stop
@section('styles')
{{HTML::style('assets/stylesheets/tagsinput/bootstrap-tagsinput.css')}}
@stop
@section('scripts')
{{HTML::script('assets/javascript/tagsinput/bootstrap-tagsinput.min.js')}}

@stop