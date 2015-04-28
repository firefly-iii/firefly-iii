@extends('layouts.default')
@section('content')
{!! Breadcrumbs::renderIfExists(Route::getCurrentRoute()->getName(), $tag) !!}
    <!-- show this block only when the tag has some meta-data -->
    @if($tag->latitude && $tag->longitude && $tag->zoomLevel)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-fw {{$subTitleIcon}} fa-fw"></i> {{{$tag->tag}}}
                        @if($tag->date)
                            on {{$tag->date->format('jS F Y')}}
                        @endif
                        <!-- ACTIONS MENU -->
                        <div class="pull-right">
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                    Actions
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right" role="menu">
                                    <li><a href="{{route('tags.edit',$tag->id)}}"><i class="fa fa-pencil fa-fw"></i> Edit tag</a></li>
                                    <li><a href="{{route('tags.delete',$tag->id)}}"><i class="fa fa-trash fa-fw"></i> Delete tag</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        @if($tag->description)
                            <p class="text-info">
                                {{$tag->description}}
                            </p>
                        @endif
                        @if($tag->latitude && $tag->longitude && $tag->zoomLevel)
                            <p>
                                <img src="https://maps.googleapis.com/maps/api/staticmap?center={{$tag->latitude}},{{$tag->longitude}}&zoom={{$tag->zoomLevel}}&size=600x300">
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- if no such thing, show another block maybe? -->
<div class="row">
    <div class="col-lg-612 col-md-12 col-sm-12 col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-repeat fa-fw"></i> Transactions
                <!-- here is the edit menu when there is no meta-data -->
                @if(!($tag->latitude && $tag->longitude && $tag->zoomLevel))
                    <!-- ACTIONS MENU -->
                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                Actions
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu pull-right" role="menu">
                                <li><a href="{{route('tags.edit',$tag->id)}}"><i class="fa fa-pencil fa-fw"></i> Edit tag</a></li>
                                <li><a href="{{route('tags.delete',$tag->id)}}"><i class="fa fa-trash fa-fw"></i> Delete tag</a></li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
            @include('list.journals-full',['journals' => $tag->transactionjournals])
        </div>
    </div>
</div>

@stop
@section('scripts')
<script type="text/javascript">
    var tagID = {{{$tag->id}}};
</script>
@stop
