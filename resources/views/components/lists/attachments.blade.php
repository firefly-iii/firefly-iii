<table class="table table-responsive table-hover">
    @foreach($attachments as $attachment)
    <tr>
        <td class="onetwenty">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('attachments.edit', $attachment->id) }}" class="btn btn-outline-secondary"><span class="bi bi-pencil"></span></a>
                <a href="{{ route('attachments.delete', $attachment->id) }}" class="btn btn-danger"><span class="bi bi-trash"></span></a>
                @if($attachment->file_exists)
                    <a href="{{ route('attachments.download', $attachment->id) }}" class="btn btn-outline-secondary"><span class="bi bi-download"></span></a>
                @endif
                @if(!$attachment->file_exists)
                    <a href="#" class="btn btn-danger"><span class="bi bi-exclamation-triangle"></span></a>
                @endif
            </div>
        </td>
        <td>
            @if($attachment->file_exists)
                <span class="bi {{ mime_icon($attachment->mime) }}"></span>
                <a href="{{ route('attachments.view', $attachment->id) }}" title="{{ $attachment->filename }}">
                    @if($attachment->title)
                        {{ $attachment->title }}
                    @else
                        {{ $attachment->filename }}
                    @endif
                </a>
                ({{ print_nice_filesize($attachment->size) }})
                @if('' !== (string)$attachment->notes_text)
                    {!! parse_markdown($attachment->notes_text) !!}
                @endif
            @endif
            @if(!$attachment->file_exists)
                <span class="bi bi-exclamation-triangle"></span>
                @if($attachment->title)
                    {{ $attachment->title }}
                @else
                    {{ $attachment->filename }}
                @endif
                <br>
                <span class="text-danger">{{ __('firefly.attachment_not_found') }}</span>
            @endif
        </td>
    </tr>
    @endforeach
</table>
