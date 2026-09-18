<div class="card-header">
    <div class="row">
        <div class="col">
            <h3 class="card-title">{{ $cardTitle }}</h3>
        </div>
        @if('' !== $route)
        <div class="col text-end" role="group">
            <div class="btn-group">
            <a href="{{ $route }}" class="btn btn-sm btn-outline-success">
                <span class="bi bi-plus-circle"></span> {{ $linkTitle }}
            </a>

            <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="card_header_menu" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="bi bi-list"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="card_header_menu">
                    <li><a class="dropdown-item" href="{{ $route }}">
                            <span class="bi bi-plus-circle"></span> {{ $linkTitle }}
                        </a></li>
                </ul>
            </div>
            </div>
        </div>
        @endif
    </div>
</div>
