@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-12">
        <h1>Firefly<br/>
            <small>Migration</small>
        </h1>
        <ol>
            <li>Upload <code>firefly-export-****-**-**.json</code></li>
            <li>Wait..</li>
            <li>Done!</li>
        </ol>

        <p>
            &nbsp;
        </p>
        {{Form::open(['files' => true,'url' => route('migrate.upload')])}}
            <div class="form-group">
                <label for="file">Export file</label>
                <input name="file" type="file" id="exportFile">
                <p class="help-block">Upload the export file here.</p>
            </div>
            <button type="submit" class="btn btn-info">Import</button>
        {{Form::close()}}
    </div>
</div>

@stop