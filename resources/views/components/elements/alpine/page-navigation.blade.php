<template x-if="sortableTable.totalPages.{{ $sortIdentifier  }} > 1" x-effect="sortableTable ? sortableTable.capturePageNavigation() : null">
    <div class="m-2">
        <nav data-sort-identifier="{{ $sortIdentifier }}">
            <ul class="pagination">
                <li :class="{'page-item': true, 'disabled': 1 === sortableTable.pages.{{ $sortIdentifier  }} }" :aria-disabled="1 === sortableTable.pages.{{ $sortIdentifier  }} ? 'true' : 'false'" aria-label="{!! __('pagination.previous') !!}">
                    <template x-if="1 === sortableTable.pages.{{ $sortIdentifier  }}">
                        <span class="page-link" aria-hidden="true">‹</span>
                    </template>
                    <template x-if="sortableTable.pages.{{ $sortIdentifier  }} > 1">
                        <a class="page-link" :data-page="(sortableTable.pages.{{ $sortIdentifier  }}-1)" :href="pageNavUrl + '?page[{{ $sortIdentifier }}]=' + (sortableTable.pages.{{ $sortIdentifier  }} - 1)" rel="prev" aria-label="{!! __('pagination.previous') !!}" title="{!! __('pagination.previous') !!}">‹</a>
                    </template>
                </li>
                <template x-for="current in sortableTable.totalPages.{{ $sortIdentifier  }}" :key="current">
                    <li class="page-item" :class="{'page-item': true, 'active': current === sortableTable.pages.{{ $sortIdentifier  }}}">
                        <template x-if="current !== sortableTable.pages.{{ $sortIdentifier  }}">
                            <a class="page-link" :data-page="current" :href="pageNavUrl+'?page[{{ $sortIdentifier }}]=' + current" x-text="current" :aria-label="current"></a>
                        </template>
                        <template x-if="current === sortableTable.pages.{{ $sortIdentifier  }}">
                            <span class="page-link" x-text="current"></span>
                        </template>
                    </li>
                </template>
                <li class="{'page-item': true, 'disabled': sortableTable.totalPages.{{ $sortIdentifier  }} === sortableTable.pages.{{ $sortIdentifier  }}" :aria-disabled="sortableTable.totalPages.{{ $sortIdentifier  }} === sortableTable.pages.{{ $sortIdentifier  }} ? 'true' : 'false'" aria-label="{!! __('pagination.next')  !!}">
                    <template x-if="sortableTable.totalPages.{{ $sortIdentifier  }} > sortableTable.pages.{{ $sortIdentifier  }}">
                        <a class="page-link" :data-page="(sortableTable.pages.{{ $sortIdentifier  }}+1)" :href="pageNavUrl+'?page[{{ $sortIdentifier }}]=' + (sortableTable.pages.{{ $sortIdentifier  }} +1)" rel="next" aria-label="{!! __('pagination.next')  !!}" title="{!! __('pagination.next')  !!}">
                            ›</a>
                    </template>
                    <template x-if="sortableTable.totalPages.{{ $sortIdentifier  }} === sortableTable.pages.{{ $sortIdentifier  }}">
                        <span class="page-link" aria-hidden="true">›</span>
                    </template>
                </li>
            </ul>
        </nav>
    </div>
</template>
