@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('attachments.destroy', $attachment->id) }}" accept-charset="UTF-8" class="form-horizontal" id="destroy">
        <input name="_token" type="hidden" value="{{ csrf_token() }}">
        <div class="row">
            <div class="col-lg-6 offset-lg-3 col-md-12 col-sm-12">
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('form.delete_attachment', ['name' => $attachment->filename]) }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">
                            {{ trans('form.permDeleteWarning') }}
                        </p>

                        <p>
                            {{ trans('form.attachment_areYouSure', ['name' => $attachment->filename]) }}
                        </p>
                    </div>
                    <div class="card-footer">
                        <input type="submit" name="submit" value="{{ trans('form.deletePermanently') }}" class="btn text-end btn-danger"/>
                        <a href="{{ URL::previous() }}" class="btn-outline-secondary btn">{{ trans('form.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
