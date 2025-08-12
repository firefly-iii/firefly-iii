<div class="col" x-data="piggies">

    <template x-for="group in piggies">
        <div class="card mb-2">
            <div class="card-header">
                <h3 class="card-title"><a href="{{ route('piggy-banks.index')  }}" title="{{ __('firefly.go_to_piggies')  }}">{{ __('firefly.piggy_banks') }}
                        (<span x-text="group.title"></span>)</a></h3>
            </div>
            <ul class="list-group list-group-flush">
                <template x-for="piggy in group.piggies">
                    <li class="list-group-item">
                        <strong x-text="piggy.name"></strong>
                        <div class="progress" role="progressbar" aria-label="Info example"
                             :aria-valuenow="piggy.percentage" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-info text-dark"
                                 :style="'width: ' + piggy.percentage +'%'">
                                <span x-text="piggy.percentage + '%'"></span>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    </template>

</div>
