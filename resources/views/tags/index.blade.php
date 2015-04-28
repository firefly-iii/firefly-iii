@extends('layouts.default')
@section('content')

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-fw fa-tags"></i> Tags</div>
            <div class="panel-body">
                <div id="tagHelp" class="collapse
                @if($helpHidden === false)
                in
                @endif
                ">
                    <p>
                        Usually tags are singular words, designed to quickly band items together
                        using things like <span class="label label-info">expensive</span>,
                        <span class="label label-info">bill</span> or
                        <span class="label label-info">for-party</span>. In Firefly III, tags can have more properties
                        such as a date, description and location. This allows you to join transactions together in a more meaningful
                        way. For example, you could make a tag called <span class="label label-success">Christmas dinner with friends</span>
                        and add information about the restaurant. Such tags are "singular", you would only use them for a single occasion,
                        perhaps with multiple transactions.
                    </p>
                    <p>
                        Tags group transactions together, which makes it possible to store reimbursements
                        (in case you front money for others) and other "balancing acts" where expenses
                        are summed up (the payments on your new TV) or where expenses and deposits
                        are cancelling each other out (buying something with saved money). It's all up to you.
                        Using tags the old-fashioned way is of course always possible.
                    </p>
                    <p>
                        Create a tag to get started or enter tags when creating new transactions.
                    </p>
                </div>
                <p>
                    <a data-toggle="collapse" id="tagHelpButton" href="#tagHelp" aria-expanded="false" aria-controls="tagHelp">
                        @if($helpHidden === false)
                            Hide help
                        @else
                            Show help
                        @endif
                    </a>
                </p>
                <p>
                    <a href="{{route('tags.create')}}" title="New tag" class="btn btn-info"><i class="fa fa-fw fa-tag"></i> Create new tag</a>
                </p>
                <p>
                    @if(count($tags) == 0)
                        <em>No tags</em>
                    @else
                        @foreach($tags as $tag)
                            <h4 style="display: inline;"><a class="label label-success" href="{{route('tags.show',$tag)}}">{{$tag->tag}}</a></h4>
                        @endforeach
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script type="text/javascript" src="js/tags.js"></script>
@endsection