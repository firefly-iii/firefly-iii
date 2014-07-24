<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <h1>Firefly
            @if($count > 0)
            <small>What's playing?</small>
            @endif
        </h1>
        @if($count > 0)
        <form role="form" method="GET">
        <?php $r = Session::get('range', '1M'); ?>
        <div class="row">
            <div class="col-lg-2">
                <button name="action" value="prev" class="btn btn-default @if($r=='1D') btn-info @endif btn-sm"
                        type="submit">&laquo; Previous {{Config::get('firefly.range_to_text.'.$r)}}
                </button>
            </div>
            <div class="col-lg-3">
                <div class="btn-group btn-group-sm">
                <button name="range" value="1D" class="btn btn-default @if($r=='1D') btn-info @endif btn-sm"
                        type="submit">1D
                </button>
                <button name="range" value="1W" class="btn btn-default @if($r=='1W') btn-info @endif btn-sm"
                        type="submit">1W
                </button>
                <button name="range" value="1M" class="btn btn-default @if($r=='1M') btn-info @endif btn-sm"
                        type="submit">1M
                </button>
                    <button name="range" value="3M" class="btn btn-default @if($r=='3M') btn-info @endif btn-sm"
                            type="submit">3M
                    </button>
                    <button name="range" value="6M" class="btn btn-default @if($r=='6M') btn-info @endif btn-sm"
                            type="submit">6M
                    </button>
                </div>
            </div>
            <div class="col-lg-2">
                <input value="{{Session::get('start')->format('Y-m-d')}}" name="start" type="date"
                       class="form-control input-sm">
            </div>
            <div class="col-lg-2">
                <input value="{{Session::get('end')->format('Y-m-d')}}" name="end" type="date"
                       class="form-control input-sm">
            </div>
            <div class="col-lg-1">
                <button class="btn btn-default btn-sm @if($r=='custom') btn-info @endif" type="submit" name="range"
                        value="custom">Custom
                </button>
            </div>
            <div class="col-lg-2" style="text-align:right;">
                <button name="action" value="next" class="btn btn-default @if($r=='1D') btn-info @endif btn-sm"
                        type="submit">&raquo; Next {{Config::get('firefly.range_to_text.'.$r)}}
                </button>
            </div>
        </div>
        </form>


        @endif

    </div>
</div>