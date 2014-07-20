@foreach($reps as $date => $data)
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h3>{{$data['date']}}</h3>
        <table class="table table-bordered table-striped">
            <tr>
                <th style="width:45%;">Budget</th>
                <th style="width:15%;">Envelope</th>
                <th style="width:15%;">Left</th>
                <th>&nbsp;</th>
            </tr>
            @foreach($data['limitrepetitions'] as $index => $rep)
            <tr>
                <td>
                    <a href="{{route('budgets.show',$rep->limit->budget->id)}}">{{{$rep->limit->budget->name}}}</a>
                </td>
                <td>
                    <span class="label label-primary">
                        <span class="glyphicon glyphicon-envelope"></span> {{mf($rep->amount,false)}}</span>
                </td>
                <td>
                @if($rep->left() < 0)
                            <span class="label label-danger">
                                <span class="glyphicon glyphicon-envelope"></span>
                                {{mf($rep->left(),false)}}</span>
                @else
                            <span class="label label-success">
                                <span class="glyphicon glyphicon-envelope"></span>
                                {{mf($rep->left(),false)}}</span>
                @endif
                </td>
                <td>
                    <a href="{{route('budgets.limits.delete',$rep->limit->id)}}" class="btn btn-xs btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                    @if($rep->limit->repeats == 1)
                        <span class="label label-warning">auto repeats</span>
                    @endif
                </td>
            </tr>
@endforeach
</table>
</div>
</div>
@endforeach