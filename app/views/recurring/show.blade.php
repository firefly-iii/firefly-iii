@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            <small>Recurring transaction "{{{$recurring->name}}}"</small>
        </h1>
        <p class="lead">Use recurring transactions to track repeated withdrawals</p>
            <p>
              <div class="btn-group btn-group-xs">
                                    <a href="{{route('recurring.edit',$recurring->id)}}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span> edit</a>
                                    <a href="{{route('recurring.delete',$recurring->id)}}" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> delete</a>
                                </div>
            </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-sm-6 col-md-12">

        <table class="table">
        <tr>
            <td>Matches on: </td>
            <td>
                @foreach(explode(' ',$recurring->match) as $word)
                    <span class="label label-info">{{{$word}}}</span>
                @endforeach
            </td>
        </tr>
        <tr>
            <td>Between</td>
            <td> {{mf($recurring->amount_min)}} &ndash; {{mf($recurring->amount_max)}}</td>
        </tr>
        <tr>
            <td>Repeats</td>
            <td>{{ucfirst($recurring->repeat_freq)}}</td>
        </tr>
        <tr>
            <td>Next reminder</td>
            <td>{{$recurring->next()->format('d-m-Y')}}</td>
        </tr>
        <tr>
        <td>Will be auto-matched</td>
        <td>
                            @if($recurring->automatch)
                                <span class="glyphicon glyphicon-ok"></span>
                            @else
                                <span class="glyphicon glyphicon-remove"></span>
                            @endif
                        </td>
        </tr>
        <tr>
        <td>Is active</td>

                <td>
                    @if($recurring->active)
                    <span class="glyphicon glyphicon-ok"></span>
                    @else
                    <span class="glyphicon glyphicon-remove"></span>
                    @endif
                </td>
        </tr>
        </table>

                <td>

                </td>
            </tr>
    </div>
</div>
@stop