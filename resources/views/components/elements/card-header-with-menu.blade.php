<div class="card-header">
    <div class="row">
        <div class="col">
            <h3 class="card-title">{{ $cardTitle }}</h3>
        </div>
        <div class="col text-end">
            <div class="dropdown">
                <button class="btn btn-sm btn-secondary-outline dropdown-toggle" type="button" id="card_header_menu" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="bi bi-list"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="card_header_menu">
                    <li><a class="dropdown-item" href="{{ $route }}"><span
                                class="bi bi-plus-circle"></span> {{ $linkTitle }}
                        </a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
