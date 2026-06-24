@extends('layout.v3.session')
@section('content')

    <form method="POST" action="{{ route('categories.destroy',$category->id) }}" accept-charset="UTF-8" class="form-horizontal" id="destroy">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('form.delete_category', ['name' => $category->name]) }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">
                            {{ trans('form.permDeleteWarning') }}
                        </p>

                        <p>
                            {{ trans('form.category_areYouSure', ['name' => $category->name]) }}
                        </p>

                        <p>
                            @if($category->transactionjournals->count())
                                {{ Lang::choice('form.category_keep_transactions', $category->transactionjournals->count(), ['count' => $category->transactionjournals->count()]) }}
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
