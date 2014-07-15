@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Preferences</small>
        </h1>

    </div>
</div>

<!-- form -->
{{Form::open(['class' => 'form-horizontal'])}}


<!-- home screen accounts -->
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>Home screen accounts</h3>
        <p class="text-info">Which accounts should be displayed on the home page?</p>
        @foreach($accounts as $account)
            <div class="form-group">
                <div class="col-sm-10">
                    <div class="checkbox">
                        <label>
                            @if(in_array($account->id,$frontpageAccounts->data) || count($frontpageAccounts->data) == 0)
                                <input type="checkbox" name="frontpageAccounts[]" value="{{$account->id}}" checked> {{{$account->name}}}
                            @else
                            <input type="checkbox" name="frontpageAccounts[]" value="{{$account->id}}"> {{{$account->name}}}
                            @endif
                        </label>
                    </div>
                </div>
            </div>
        @endforeach


    </div>
</div>

<!-- home screen accounts -->
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>Home view range</h3>
        <p class="text-info">By default, Firefly will show you one month of data.</p>
        <div class="radio">
            <label>
                <input type="radio" name="viewRange" value="1D" @if($viewRange == '1D') checked @endif>
                One day
            </label>
        </div>

        <div class="radio">
            <label>
                <input type="radio" name="viewRange" value="1W" @if($viewRange == '1W') checked @endif>
                One week
            </label>
        </div>

        <div class="radio">
            <label>
                <input type="radio" name="viewRange" value="1M" @if($viewRange == '1M') checked @endif>
                One month
            </label>
        </div>

        <div class="radio">
            <label>
                <input type="radio" name="viewRange" value="3M" @if($viewRange == '3M') checked @endif>
                Three months
            </label>
        </div>

        <div class="radio">
            <label>
                <input type="radio" name="viewRange" value="6M" @if($viewRange == '6M') checked @endif>
                Six months
            </label>
        </div>




    </div>
</div>


<!-- submit button -->

<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>Submit</h3>
        <div class="form-group">
            <div class="col-sm-12">
                <button type="submit" class="btn btn-default">Save settings</button>
            </div>
        </div>
    </div>
</div>

<!-- form close -->
{{Form::close()}}

@stop
@section('scripts')
@stop