@extends('layouts.default')
@section('content')
<div class="row">
    <div class="col-lg-8 col-md-8 col-sm-12">
        <p class="text-info">
            Read <a href="https://github.com/JC5/firefly-iii/wiki/Importing-data-from-Firefly-II">the wiki</a> to read more about how data migration.
        </p>
        <ol>
            <li>Upload <code>firefly-export-****-**-**.json</code></li>
            <li>Wait..</li>
            <li>Done!</li>
        </ol>
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