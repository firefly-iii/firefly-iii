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
    <div class="col-lg-12 col-md-12 col-sm-12 data-holder">
        <div class="card" id="category-index">
            <x-elements.card-header-with-menu :cardTitle="trans('firefly.categories')"
                                              :route="route('categories.create') . '?_from=' . urlencode($FF3_FROM)"
                                              :linkTitle="__('firefly.create_new_category')"/>
            <div class="card-body p-0">
                <template x-if="totalPages > 1">
                    <x-elements.alpine.page-navigation/>
                </template>
                <table data-sort-identifier="main" class="table table-valign-middle table-sm table-hover sortable">
                    <thead>
                    <tr>
                        <th data-sort-column="name" data-filter-column="name" class="w-40"><span class="title">{{ trans('list.name') }}</span></th>
                        <th data-sort-column="last_activity"><span class="title">{{ trans('list.last_activity') }}</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="category in categories" :key="category.id">
                        <tr>
                            <td>
                                <a :href="'./categories/show/' + category.id" :title="category.name"
                                   x-text="category.name"></a>
                            </td>
                            <td>
                                <span x-text="category.last_activity"></span>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>

                <template x-if="totalPages > 1">
                    <x-elements.alpine.page-navigation/>
                </template>

                <x-elements.card-footer-with-menu
                    :route="route('categories.create') . '?_from=' . urlencode($FF3_FROM)"
                    :linkTitle="__('firefly.create_new_category')"/>

            </div>
        </div>

        <template x-if="0 === categories.length && false === loadingPage && false === sortableTable.isFiltering">
            <x-empty-page :route="route('categories.create') . '?_from=' . urlencode($FF3_FROM)"
                          type="categories" object-type=""/>
        </template>

    </div>
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
