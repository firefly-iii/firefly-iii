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
                    <div class="btn-group">
                        <a href="{{route('budgets.limits.edit',$rep->limit->id)}}" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                        <a href="{{route('budgets.limits.delete',$rep->limit->id)}}" class="btn btn-xs btn-danger"><span class="glyphicon glyphicon-trash"></span></a>
                    </div>
                    @if($rep->limit->repeats == 1)
                        <span class="label label-warning">auto repeats</span>
                    @endif
                    <a href="{{route('budgets.limits.create',$rep->limit->budget->id)}}" class="btn btn-default btn-xs"><span
                            class="glyphicon-plus-sign glyphicon"></span> Add another limit</a>
                </td>
            </tr>
@endforeach
</table>
</div>
</div>
@endforeach