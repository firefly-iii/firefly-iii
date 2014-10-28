@extends('layouts.default')
@section('content')


<div id="sankey_multiple" style="width: 900px; height: 400px;"></div>


</body>
</html>
@stop
@section('scripts')
<script type="text/javascript" src="https://www.google.com/jsapi?autoload={'modules':[{'name':'visualization','version':'1.1','packages':['sankey']}]}">
</script>
<script type="text/javascript">
google.setOnLoadCallback(drawChart);
   function drawChart() {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'From');
    data.addColumn('string', 'To');
    data.addColumn('number', 'Weight');
    data.addRows([
    <?php $c = 0;?>
        @foreach($filtered as $index => $entry)
        [ '{{{$entry['from']}}}', '{{{$entry['to']}}}', {{{$entry['amount']}}} ], // {{$c}}
        <?php  $c++ ?>
        @endforeach

    ]);

    // Set chart options
    var options = {
        sankey: {
                link: { color: { fill: '#9fa8da', fillOpacity: 0.8 } },
                node: { color: { fill: '#000' },
                        label: { color: '#000' } }
              }
    };

    // Instantiate and draw our chart, passing in some options.
    var chart = new google.visualization.Sankey(document.getElementById('sankey_multiple'));
    chart.draw(data, options);
   }
</script>
@stop