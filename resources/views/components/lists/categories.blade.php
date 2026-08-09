<div class="pl-3">
    {{ $categories->links('pagination.bootstrap-4') }}
</div>
<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsign="az">{{ trans('list.name') }}</th>
        <th data-defaultsign="month">{{ trans('list.lastActivity') }}</th>
        <th data-defaultsort="disabled">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><a href="{{ route('categories.no-category') }}"><em>{{ __('firefly.without_category') }}</em></a></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    @foreach($categories as $category)
    <tr>
        <td data-value="{{ $category->name }}">
            <a href="{{ route('categories.show', [$category->id]) }}" title="{{ $category->name }}">{{ $category->name }}</a>
            @if($category->attachments->count() > 0)
                <span class="bi bi-paperclip"></span>
            @endif
        </td>
        @if(null !== $category->lastActivity)
        <td data-value="{{ $category->lastActivity->format('Y-m-d H-i-s') }}">
            {{ $category->lastActivity->isoFormat($monthAndDayFormat) }}
        </td>
        @else
        <td data-value="0000-00-00 00-00-00">
            <em>{{ __('firefly.never') }}</em>
        </td>
        @endif
        <td class="text-end">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ __('firefly.actions') }}
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('categories.edit', [$category->id]) }}?_from={{ urlencode($FF3_FROM) }}"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                    <li><a class="dropdown-item text-danger" href="{{ route('categories.delete', [$category->id]) }}?_from={{ urlencode($FF3_FROM) }}"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                </ul>
            </div>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
<div class="pl-3">
    {{ $categories->links('pagination.bootstrap-4') }}
</div>
