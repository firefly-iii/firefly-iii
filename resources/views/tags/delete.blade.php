@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('tags.destroy',$tag->id) }}" accept-charset="UTF-8" class="form-horizontal" id="destroy">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-6 col-sm-12">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('firefly.delete_tag',['tag' => $tag->tag]) }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">
                            {{ trans('form.permDeleteWarning') }}
                        </p>

                        <p>
                            {{ trans('form.tag_areYouSure', ['tag' => $tag->tag]) }}
                        </p>

                        <p>
                            @if($tag->transactionJournals->count() > 0)
                                {{ trans_choice('form.tag_keep_transactions', $tag->transactionjournals->count(), ['count' => $tag->transactionjournals->count()]) }}
                            @endif
                        </p>
                    </div>
                    <div class="card-footer text-end">
                        <input type="submit" name="submit" value="{{ trans('form.deletePermanently') }}" class="btn btn-danger"/>
                        <a href="{{ URL::previous() }}" class="btn-outline-secondary btn">{{ trans('form.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('scripts')
    @vite(['js/pages/generic.js'])
@endsection
