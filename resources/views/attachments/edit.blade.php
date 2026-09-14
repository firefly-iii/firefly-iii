@extends('layout.v3.session')
@section('content')
    <form method="POST" action="{{ route('attachments.update', $attachment->id) }}" accept-charset="UTF-8"
          class="form-horizontal" id="update">

        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
        <input type="hidden" name="id" value="{{ $attachment->id }}"/>


        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.mandatoryFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::staticText('filename',$attachment->filename) !!}
                        {!! ExpandedForm::staticText('mime',$attachment->mime) !!}
                        {!! ExpandedForm::staticText('size',$attachment->size) !!}
                    </div>
                </div>

            </div>
            <div class="col-lg-6 col-md-6 col-sm-12">
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.optionalFields') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::text('title', $attachment->title) !!}
                        {!! ExpandedForm::textarea('notes',null,['helpText' => trans('firefly.field_supports_markdown')]) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                {{-- panel for options --}}
                <div class="card mb-2">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('firefly.options') }}</h3>
                    </div>
                    <div class="card-body">
                        {!! ExpandedForm::optionsList('update','attachment') !!}
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn  btn-success">
                            {{ __('firefly.update_attachment') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>


    </form>
@endsection
