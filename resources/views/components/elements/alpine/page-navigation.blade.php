<template x-if="totalPages > 1">
    <div class="m-2">
        <nav>
            <ul class="pagination">
                <li :class="{'page-item': true, 'disabled': 1 === page}" :aria-disabled="1 === page ? 'true' : 'false'" aria-label="{!! __('pagination.previous') !!}">
                    <template x-if="1 === page">
                        <span class="page-link" aria-hidden="true">‹</span>
                    </template>
                    <template x-if="page > 1">
                        <a class="page-link" :href="pageNavUrl + '?page=' + (page - 1)" rel="prev" aria-label="{!! __('pagination.previous') !!}" title="{!! __('pagination.previous') !!}">‹</a>
                    </template>
                </li>
                <template x-for="current in totalPages" :key="current">
                    <li class="page-item" :class="{'page-item': true, 'active': current === page}">
                        <template x-if="current !== page">
                            <a class="page-link" :href="pageNavUrl+'?page=' + current" x-text="current" :aria-label="current"></a>
                        </template>
                        <template x-if="current === page">
                            <span class="page-link" x-text="current"></span>
                        </template>
                    </li>
                </template>
                <li class="{'page-item': true, 'disabled': totalPages === page" :aria-disabled="totalPages === page ? 'true' : 'false'" aria-label="{!! __('pagination.next')  !!}">
                    <template x-if="totalPages > page">
                        <a class="page-link" :href="pageNavUrl+'?page=' + (page +1)" rel="next" aria-label="{!! __('pagination.next')  !!}" title="{!! __('pagination.next')  !!}">›</a>
                    </template>
                    <template x-if="totalPages === page">
                        <span class="page-link" aria-hidden="true">›</span>
                    </template>
                </li>
            </ul>
        </nav>
    </div>
</template>
