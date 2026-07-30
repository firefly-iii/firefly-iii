@extends('layout.v3.debug')
@section('content')
<style nonce="{{ $JS_NONCE }}">
    p.default-text {font-family:Arial, Arial, Helvetica, sans-serif;font-size:12@pt;width:600px;}
    p.warn-text {font-family:Arial, Arial, Helvetica, sans-serif;font-size:12pt;width:600px;color:#a00;}
    textarea {font-family:Menlo, Monaco, Consolas, monospace;font-size:8pt !important;}
</style>
<p>
    {!! trans('firefly.debug_submit_instructions') !!}
</p>
<p>
    {{ __('firefly.debug_no_private') }}
</p>
<p>
    {{ trans('firefly.debug_pretty_table') }}
</p>
<label for="debug_info"></label>
<textarea rows="30" class="form-control" name="debug_info" id="debug_info">
Debug information generated at {{ $now }} for Firefly III version **{{ !$FF_IS_DEVELOP ? 'v' : '' }}{{ $FF_VERSION }}**.

{{ $table }}
</textarea>
<script type="text/javascript" nonce="{{ $JS_NONCE }}">
    var textArea = document.getElementById('debug_info');
    var text = textArea.value;
    var timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    text = text.replace('[BrowserTZ]', timeZone);
    textArea.value = text;
</script>

<p>
    <a href="{{ route('index') }}">{{ trans('firefly.back_to_index') }}</a>
</p>

<p class="text-danger">
    {!! trans('firefly.debug_additional_data') !!}
</p>

<textarea rows="30" class="form-control" name="log_info">
```
{{ $logContent }}
```
</textarea>

<p>
    <a href="{{ route('index') }}">{{ trans('firefly.back_to_index') }}</a>
</p>

</body>
</html>
@endsection
