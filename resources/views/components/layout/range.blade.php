<li class="nav-item dropdown">
    <a
        href="#"
        class="nav-link"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        id="date-range"
    >
        <em class="bi bi-calendar"></em>
        <span class="daterange-holder"></span>
    </a>
    <ul
        x-data="dates" x-bind="eventListeners"
        class="dropdown-menu"
        aria-labelledby="date-range"
        style="--bs-dropdown-min-width: 8rem"
    >
        <li>
            <a href="#" class="dropdown-item daterange-current" @click="changeDateRange">current</a>
        </li>
        <li>
            <a href="#" @click="changeDateRange" class="dropdown-item daterange-next">next</a>
        </li>
        <li>
            <a href="#" class="dropdown-item daterange-prev" @click="changeDateRange">prev</a>
        </li>
        <li>
            <a href="#" class="dropdown-item daterange-7d" @click="changeDateRange">{{ __('firefly.last_seven_days') }}</a>
        </li>
        <li>
            <a href="#" class="dropdown-item daterange-30d" @click="changeDateRange">
                {{ __('firefly.last_thirty_days') }}
            </a>
        </li>
        <li>
            <a href="#" class="dropdown-item daterange-mtd" @click="changeDateRange">
                {{ __('firefly.month_to_date') }}
            </a>
        </li>
        <li>
            <a href="#" class="dropdown-item daterange-ytd" @click="changeDateRange">
                {{ __('firefly.year_to_date') }}
            </a>
        </li>
        <li>
            <a href="#" type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#customDateRangeModal">
                {{ __('firefly.customRange') }}
            </a>
        </li>
    </ul>
</li>


