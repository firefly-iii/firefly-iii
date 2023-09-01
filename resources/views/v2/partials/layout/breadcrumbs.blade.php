@if(count($breadcrumbs) > 0)
    <ol class="breadcrumb float-sm-end">
        @foreach ($breadcrumbs as $bc)
            @if($bc->url and !$loop->last)
                <li class="breadcrumb-item"><a href="{{ $bc->url }}">{{ $bc->title }}</a></li>
            @else
                <li class="breadcrumb-item active">{{ $bc->title }}</li>
            @endif
        @endforeach
    </ol>
@endif
