<table class="table table-striped table-bordered">
    <tr>
        <th>&nbsp;</th>
        <th>Name</th>
        <th>Last activity</th>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td><a href="{{route('categories.noCategory')}}"><em>Without a category</em></a></td>
        <td>&nbsp;</td>
    </tr>
    @foreach($categories as $category)
    <tr>
        <td>
            <div class="btn-group btn-group-xs">
                <a href="{{route('categories.edit',$category->id)}}" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-pencil"></span></a>
                <a href="{{route('categories.delete',$category->id)}}" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-trash"></span></a>
            </div>
        </td>
        <td>
            <a href="{{route('categories.show',$category->id)}}" title="{{{$category->name}}}">{{{$category->name}}}</a>
        </td>
        <td>
            @if($category->lastActivity)
                {{$category->lastActivity->format('jS F Y')}}
            @else
                <em>Never</em>
            @endif
        </td>
    </tr>
    @endforeach
</table>
