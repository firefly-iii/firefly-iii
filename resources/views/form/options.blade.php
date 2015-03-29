@if($type == 'create')
    <!--
    <div class="form-group">
        <label for="{{{$name}}}_store" class="col-sm-4 control-label">Store</label>
        <div class="col-sm-8">
            <div class="radio">
                <label>
                    {!! Form::radio('post_submit_action', 'store', $previousValue == 'store', ['id' => $name . '_store']) !!}
                    Store {{{$name}}}
                </label>
            </div>
        </div>
    </div>
    -->
@endif
@if($type == 'update')
    <!--
    <div class="form-group">
        <label for="{{{$name}}}_update" class="col-sm-4 control-label">Update</label>
        <div class="col-sm-8">
            <div class="radio">
                <label>
                    {!! Form::radio('post_submit_action', 'update', $previousValue == 'update' || $previousValue == 'store', ['id' => $name . '_update']) !!}
                    Update {{{$name}}}
                </label>
            </div>
        </div>
    </div>
    -->
@endif
        <!--
<div class="form-group">
    <label for="{{{$name}}}_validate_only" class="col-sm-4 control-label">Validate only</label>
    <div class="col-sm-8">
        <div class="radio">
            <label>
                {!! Form::radio('post_submit_action', 'validate_only', $previousValue == 'validate_only', ['id' => $name . '_validate_only']) !!}
                Only validate, do not save
            </label>
        </div>
    </div>
</div>
-->
@if($type == 'create')
    <div class="form-group">
        <label for="{{{$name}}}_return_to_form" class="col-sm-4 control-label">
            Return here
        </label>
        <div class="col-sm-8">
            <div class="radio">
                <label>
                    {!! Form::checkbox('create_another', '1') !!}
                    After storing, return here to create another one.
                </label>
            </div>
        </div>
    </div>
@endif

@if($type == 'update')
    <div class="form-group">
        <label for="{{{$name}}}_return_to_edit" class="col-sm-4 control-label">
            Return here
        </label>
        <div class="col-sm-8">
            <div class="radio"><label>
                    {!! Form::checkbox('return_to_edit', '1') !!}
                After updating, return here.
                </label>
            </div>
        </div>
    </div>
@endif
