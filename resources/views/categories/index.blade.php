@extends('layout.v3.session')
@section('content')
    <div class="row" x-data="index">
        <template x-if="true === loadingPage">
            <div class="col-lg-12 text-center">
                <div id="status-box" class="p-3 install-box-border">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">{{ __('firefly.thinking') }}</span>
                    </div>
                </div>
            </div>
        </template>
        <template x-for="(data, tableId) in groupedObjects" :key="tableId">
            <div class="col-lg-12 col-md-12 col-sm-12 data-holder">
                <div class="card" id="category-index">
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <template x-if="'ungrouped' === data.title">
                                    <h3 class="card-title" x-text="i18next.t('firefly.default_group_title_name')"></h3>
                                </template>
                                <template x-if="'ungrouped' !== data.title">
                                    <h3 class="card-title" x-text="data.title"></h3>
                                </template>
                            </div>
                            <div class="col text-end" role="group">
                                <a href="{{ route('categories.create') . '?_from=' . urlencode($FF3_FROM) }}" class="btn btn-sm btn-outline-success">
                                    <span class="bi bi-plus-circle"></span> {{ __('firefly.create_new_category') }}
                                </a>
                            </div>
                        </div>
                    </div>
                <div class="card-body p-0">
                    <table :data-sort-identifier="tableId" class="table table-valign-middle table-sm table-hover sortable">
                    <thead>
                    <template x-if="sortableTable.totalPages[tableId] > 1">
                        <tr>
                            <th colspan="3">
                                <x-elements.alpine.page-navigation />
                            </th>
                        </tr>
                    </template>
                    <tr>
                        <th data-sort-column="name" data-filter-column="name" class="w-40"><span class="title">{{ trans('list.name') }}</span></th>
                        <th data-sort-column="last_activity"><span class="title">{{ trans('list.last_activity') }}</span></th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="category in data.items" :key="category.id">
                        <tr>
                            <td>
                                <a :href="'./categories/show/' + category.id" :title="category.name"
                                   x-text="category.name"></a>
                            </td>
                            <td>
                                <span x-text="category.last_activity"></span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ __('firefly.actions') }}
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" :href="'./categories/edit/' + category.id + '?_from={{ urlencode($FF3_FROM) }}'"><span class="bi bi-pencil"></span> {{ __('firefly.edit') }}</a></li>
                                        <li><a class="dropdown-item text-danger" :href="'./categories/delete/' + category.id + '?_from={{ urlencode($FF3_FROM) }}'"><span class="bi bi-trash"></span> {{ __('firefly.delete') }}</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                        <template x-if="sortableTable.totalPages[tableId] > 1">
                        <tfoot>
                            <tr>
                                <th colspan="3">
                                    <x-elements.alpine.page-navigation />
                                </th>
                            </tr>
                        </tfoot>
                        </template>
                </table>

                <template x-if="sortableTable.totalPages.main > 1">
                    <x-elements.alpine.page-navigation sort-identifier="main"/>
                </template>

                <x-elements.card-footer-with-menu
                    :route="route('categories.create') . '?_from=' . urlencode($FF3_FROM)"
                    :linkTitle="__('firefly.create_new_category')"/>

            </div>
        </div>

                {{--
        <template x-if="0 === groupedObjects.ungrouped.length && false === loadingPage && false === sortableTable.isFiltering">
            <x-empty-page :route="route('categories.create') . '?_from=' . urlencode($FF3_FROM)"
                          type="categories" object-type=""/>
        </template>
        --}}

    </div>
        </template>
    </div>



    {{--
    @if(0 === $categories->count() && 1 === $page)
        @php
            $shownDemo = true
        @endphp
        <x-empty-page :route="route('categories.create')" type="categories" object-type="default" />
    @endif
    @if($categories->count() > 0)
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card mb-2">
                    <x-elements.card-header-with-menu :cardTitle="trans('firefly.categories')" :route="route('categories.create')" :linkTitle="__('firefly.new_category')"/>

                    <div class="card-body p-0">
                        <x-lists.categories :categories="$categories" />
                    </div>
                    <x-elements.card-footer-with-menu :route="route('categories.create')" :linkTitle="__('firefly.new_category')" />
                </div>

            </div>
        </div>
    @endif
--}}
@endsection
@section('styles')

@endsection

@section('scripts')
    @vite(['js/pages/categories/index.js'])
@endsection
