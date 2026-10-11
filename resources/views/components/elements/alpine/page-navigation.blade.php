<template x-if="sortableTable.totalPages[tableId] > 1" x-effect="sortableTable ? sortableTable.capturePageNavigation() : null">
    <div class="m-2">
        <nav :data-sort-identifier="tableId">
            <ul class="pagination">
                <li :class="{'page-item': true, 'disabled': 1 === sortableTable.pages[tableId] }" :aria-disabled="1 === sortableTable.pages[tableId] ? 'true' : 'false'" aria-label="{!! __('pagination.previous') !!}">
                    <template x-if="1 === sortableTable.pages[tableId]">
                        <span class="page-link" aria-hidden="true">‹</span>
                    </template>
                    <template x-if="sortableTable.pages[tableId] > 1">
                        <a class="page-link" :data-page="(sortableTable.pages[tableId]-1)" :href="pageNavUrl + '?page[' + tableId +']=' + (sortableTable.pages[tableId] - 1)" rel="prev" aria-label="{!! __('pagination.previous') !!}" title="{!! __('pagination.previous') !!}">‹</a>
                    </template>
                </li>
                <template x-for="current in sortableTable.totalPages[tableId]" :key="current">
                    <li class="page-item" :class="{'page-item': true, 'active': current === sortableTable.pages[tableId]}">
                        <template x-if="current !== sortableTable.pages[tableId]">
                            <a class="page-link" :data-page="current" :href="pageNavUrl+'?page[' + tableId +']=' + current" x-text="current" :aria-label="current"></a>
                        </template>
                        <template x-if="current === sortableTable.pages[tableId]">
                            <span class="page-link" x-text="current"></span>
                        </template>
                    </li>
                </template>
                <li class="{'page-item': true, 'disabled': sortableTable.totalPages[tableId] === sortableTable.pages[tableId]" :aria-disabled="sortableTable.totalPages[tableId] === sortableTable.pages[tableId] ? 'true' : 'false'" aria-label="{!! __('pagination.next')  !!}">
                    <template x-if="sortableTable.totalPages[tableId] > sortableTable.pages[tableId]">
                        <a class="page-link" :data-page="(sortableTable.pages[tableId]+1)" :href="pageNavUrl+'?page[' + tableId +']=' + (sortableTable.pages[tableId] +1)" rel="next" aria-label="{!! __('pagination.next')  !!}" title="{!! __('pagination.next')  !!}">
                            ›</a>
                    </template>
                    <template x-if="sortableTable.totalPages[tableId] === sortableTable.pages[tableId]">
                        <span class="page-link" aria-hidden="true">›</span>
                    </template>
                </li>
            </ul>
        </nav>
    </div>
</template>
