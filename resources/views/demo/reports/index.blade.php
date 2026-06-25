{{ trans('demo.reports-index-start')|raw }}
<br/>
<br/>
{{ trans('demo.reports-index-examples', {
    one: route('reports.report.default', ['1,2,3','currentMonthStart','currentMonthEnd']),
    two: route('reports.report.default', ['1,2,3','20160101','20161231']),
    three: route('reports.report.budget', ['1,2,3','2,1','20160101','20161231'])})|raw }}
