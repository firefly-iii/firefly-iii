<footer class="app-footer">
    <!--begin::To the end-->
    <div class="float-end d-none d-sm-inline">
        <a href="{{ route('debug') }}">
            @if(str_starts_with($FF_VERSION, 'develop'))
                <span class="text-danger">{{ $FF_VERSION }}</span>
            @else
                v{{ $FF_VERSION }}
            @endif
        </a>
    </div>
    <!--end::To the end-->
    <!--begin::Copyright-->
    <a href="https://github.com/firefly-iii/firefly-iii/">Firefly III</a> &copy; James Cole,
    <a href="https://github.com/firefly-iii/firefly-iii/blob/main/LICENSE">AGPL-3.0-or-later</a>
    <!--end::Copyright-->
</footer>
