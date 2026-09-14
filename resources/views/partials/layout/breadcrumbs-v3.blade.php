@if(count($breadcrumbs) > 0)
<ol class="breadcrumb float-sm-end">
    @foreach($breadcrumbs as $breadcrumb)
        @if($breadcrumb->url && !$loop->last)
            <li class="breadcrumb-item"><a href="{{ $breadcrumb->url }}">{{ $breadcrumb->title }}</a></li>
        @endif
            @if(!$breadcrumb->url || $loop->last)
                <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb->title }}</li>
            @endif
    @endforeach

</ol>
@endif
