@extends('layout.v2')
@section('scripts')
    @vite(['resources/assets/v2/pages/administrations/index.js'])
@endsection
@section('content')
    <div class="app-content">
        <div class="container-fluid" x-data="index">
            <x-messages></x-messages>
            <div class="row mb-3">
                <div class="col">
                    <p>
                        <a href="{{route('administrations.create')}}"
                           class="btn btn-primary">{{ __('firefly.create_administration') }}</a>
                    </p>
                </div>
            </div>
            <div class="row mb-3">
                <template x-for="(group, index) in userGroups" :key="index">
                    <div class="col-xl-4 col-lg-4 col-sm-6 col-xs-12 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Administration "<span x-text="group.title"></span>"</h3>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <template x-if="'' !== group.owner">
                                        <li x-text="group.owner"></li>
                                    </template>
                                    <template x-if="'' !== group.you">
                                        <li x-text="group.you"></li>
                                    </template>
                                </ul>
                                <template x-if="group.memberCountExceptYou > 0">
                                    <div>
                                        <h5>{{ __('firefly.other_users_in_admin') }}</h5>
                                    <ul>
                                        <template x-for="(member, jndex) in group.members" :key="jndex">
                                            <li>
                                                <span x-text="member.email"></span>
                                                <ul>
                                                <template x-for="(role, kndex) in member.roles" :key="kndex">
                                                    <li x-text="role"></li>
                                                </template>
                                                </ul>
                                            </li>
                                        </template>
                                    </ul>
                                    </div>
                                </template>
                                TODO Last changes: date<br>
                                TODO features in use (icons)
                            </div>
                            <div class="card-footer">
                                <div class="btn-group">
                                    <template x-if="false === group.in_use">
                                    <a href="#" class="btn btn-primary">
                                        <em class="fa-solid fa-coins"></em> Use
                                    </a>
                                    </template>
                                    <template x-if="true === group.isOwner">
                                    <a href="#" class="btn btn-primary">
                                        <em class="fa-solid fa-pencil"></em> Edit
                                    </a>
                                    </template>
                                    <template x-if="true === group.isOwner">
                                    <a href="#" class="btn btn-primary">
                                        <em class="fa-solid fa-users"></em> Access rights
                                    </a>
                                    </template>
                                    <template x-if="true === group.isOwner">
                                    <a href="#" class="btn btn-danger text-white">
                                        <em class="fa-solid fa-trash"></em> Delete
                                    </a>
                                    </template>
                                    <template x-if="true !== group.isOwner">
                                    <a href="#" class="btn btn-warning">
                                        <em class="fa-solid fa-person-walking-dashed-line-arrow-right"></em> Leave
                                    </a>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <p>
                        <a href="{{route('administrations.create')}}"
                           class="btn btn-primary">{{ __('firefly.create_administration') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
