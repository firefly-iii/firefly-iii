@if(count($breadcrumbs) > 0)
    <ol class="breadcrumb float-sm-end">
        @foreach ($breadcrumbs as $bc)
            @if($bc->url and !$loop->last)
                <li><a href="{{ $bc->url }}">{{ $bc->title }}</a></li>
            @else
                <li class="active">{{ $bc->title }}</li>
            @endif
        @endforeach
    </ol>
@endif
