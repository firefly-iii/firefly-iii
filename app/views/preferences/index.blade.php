@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Preferences</small>
        </h1>

    </div>
</div>

<!-- home screen accounts -->
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>Home screen accounts</h3>
        <p class="text-info">Which accounts should be displayed on the home page?</p>
        <!-- form -->
        {{Form::open(['class' => 'form-horizontal'])}}
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
            <div class="form-group">
                <div class="col-sm-12">
                    <button type="submit" class="btn btn-default">Save accounts</button>
                </div>
            </div>
        </form>
    </div>
</div>

@stop
@section('scripts')
@stop