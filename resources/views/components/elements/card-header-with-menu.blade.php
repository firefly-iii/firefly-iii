<div class="card-header">
    <div class="row">
        <div class="col">
            <h3 class="card-title">{{ $cardTitle }}</h3>
        </div>
        @if('' !== $route)
        <div class="col text-end" role="group">
            <a href="{{ $route }}" class="btn btn-sm btn-outline-success">
                <span class="bi bi-plus-circle"></span> {{ $linkTitle }}
            </a>
        </div>
        @endif
    </div>
</div>
