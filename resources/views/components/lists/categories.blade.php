<div class="pl-3">
    {{ $categories->links('pagination.bootstrap-4') }}
</div>
<table class="table table-hover sortable">
    <thead>
    <tr>
        <th data-defaultsort="disabled">&nbsp;</th>
        <th data-defaultsign="az">{{ trans('list.name') }}</th>
        <th data-defaultsign="month" class="hidden-sm hidden-xs">{{ trans('list.lastActivity') }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>&nbsp;</td>
        <td><a href="{{ route('categories.no-category') }}"><em>{{ __('firefly.without_category') }}</em></a></td>
        <td class="hidden-sm hidden-xs">&nbsp;</td>
    </tr>
    @foreach($categories as $category)
    <tr>
        <td>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('categories.edit', [$category->id]) }}" class="btn btn-outline-secondary btn-xs"><span class="bi bi-pencil"></span></a>
                <a href="{{ route('categories.delete', [$category->id]) }}" class="btn btn-danger btn-xs"><span class="bi bi-trash"></span></a>
            </div>
        </td>
        <td data-value="{{ $category->name }}">
            <a href="{{ route('categories.show', [$category->id]) }}" title="{{ $category->name }}">{{ $category->name }}</a>
            @if($category->attachments->count() > 0)
                <span class="bi bi-paperclip"></span>
            @endif
        </td>
        @if(null !== $category->lastActivity)
        <td class="hidden-sm hidden-xs" data-value="{{ $category->lastActivity->format('Y-m-d H-i-s') }}">
            {{ $category->lastActivity->isoFormat($monthAndDayFormat) }}
        </td>
        @else
        <td class="hidden-sm hidden-xs" data-value="0000-00-00 00-00-00">
            <em>{{ __('firefly.never') }}</em>
        </td>
        @endif
    </tr>
    @endforeach
    </tbody>
</table>
<div class="pl-3">
    {{ $categories->links('pagination.bootstrap-4') }}
</div>
