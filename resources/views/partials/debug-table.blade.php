<table>
    <thead>
    <tr>
        <th colspan="2">System information</th>
    </tr>
    <tr>
        <th>Item</th>
        <th>Value</th>
    </tr>
    </thead>
    <tbody>
    {{-- Firefly III version --}}
    <tr>
        <td>Firefly III</td>
        <td>{!! $FF_IS_DEVELOP ? '<!-- .Z9JBCmw64Zkx1pQw -->' : 'v' !!}{{ $FF_VERSION }}</td>
    </tr>
    <tr>
        <td>Build time</td>
        <td>{{ $system['build_time_nice'] }} ({{ $system['build_time'] }})</td>
    </tr>
    {{-- PHP version + settings --}}
    <tr>
        <td>PHP version</td>
        <td>{{ $system['php_version'] }} ({{ $system['bits'] }}bits) / {{ $system['interface'] }} / {{ $system['php_os'] }} {{ $system['uname'] }}</td>
    </tr>
    <tr>
        <td>BCscale</td>
        <td>{{ $system['bcscale'] }}</td>
    </tr>
    <tr>
        <td>Error reporting</td>
        <td>Display: {{ $system['display_errors'] }}, reporting: {{ $system['error_reporting'] }}</td>
    </tr>
    <tr>
        <td>Max upload</td>
        <td>{{ $system['upload_size'] }} ({{ $system['upload_size'] }})</td>
    </tr>
    <tr>
        <td>Database drivers</td>
        <td>
            @foreach($system['all_drivers'] as $driver)
                @if($driver === $system['current_driver'])
                    <strong>*{{ $driver }}*</strong>{{ !$loop->last ? ', ' : '' }}
                @else
                    {{ $driver }}{{ !$loop->last ? ', ' : '' }}
                @endif
            @endforeach
        </td>
    </tr>
    {{-- Docker information --}}
    @if($docker['is_docker'])
        <tr>
            <td>
                Docker build
            </td>
            <td>
                <span>#</span>{{ $docker['build'] }}, base <span>#</span>{{ $docker['base_build'] }}
            </td>
        </tr>
    @endif
    </tbody>
</table>
<table>
    <thead>
    <tr>
        <th colspan="2">Firefly III information</th>
    </tr>
    <tr>
        <th>Item</th>
        <th>Value</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Timezone</td>
        <td>{{ config('app.timezone') }} + [BrowserTZ]</td>
    </tr>
    <tr>
        <td>App environment</td>
        <td>{{ config('app.env') }}, debug: {{ $app['debug'] }}</td>
    </tr>
    <tr>
        <td>Layout</td>
        <td>{{ config('view.layout') }}</td>
    </tr>
    <tr>
        <td>Logging</td>
        <td>{{ config('logging.level') }}, {{ config('logging.default') }} / {{ $app['audit_log_channel'] }}</td>
    </tr>
    <tr>
        <td>Cache driver, session driver</td>
        <td>{{ config('cache.default') }}, {{ config('session.driver') }}</td>
    </tr>
    <tr>
        <td>Default language and locale</td>
        <td>{{ $app['default_language']}} + {{ $app['default_locale'] }}</td>
    </tr>
    <tr>
        <td>Trusted proxies</td>
        <td><code>{{ config('firefly.trusted_proxies') }}</code></td>
    </tr>
    <tr>
        <td>Login provider & user guard</td>
        <td>{{ config('auth.providers.users.driver') }} / {{ config('auth.defaults.guard') }}</td>
    </tr>
    <tr>
        <td>Login headers</td>
        <td>{{ $app['remote_header'] }} + {{ $app['remote_mail_header'] }}</td>
    </tr>
    {{--
    <tr>
        <td>Stateful domains</td>
        <td>{{ $app['stateful_domains'] }}</td>
    </tr>
    --}}
    <tr>
        <td>Last cron job</td>
        <td>{{ $app['last_cronjob'] }} ({{ $app['last_cronjob_ago'] }})</td>
    </tr>
    <tr>
        <td>Mailer</td>
        <td>{{ config('mail.default') }}</td>
    </tr>
    <tr>
        <td>Exchange rates</td>
        <td>
            {{ get_app_configuration('enable_exchange_rates', true) ? 'Enabled' : 'Disabled' }}, downloads {{ get_app_configuration('enable_external_rates')  ? 'enabled' : 'disabled' }}
        </td>
    </tr>
    <tr>
        <td>RB-column</td>
        <td>{{ get_app_configuration('use_running_balance', true) ? 'Enabled' : 'Disabled' }}</td>
    </tr>
    </tbody>
</table>

<table>
    <thead>
    <tr>
        <th colspan="2">User-specific information</th>
    </tr>
    <tr>
        <th>Item</th>
        <th>Value</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>User</td>
        <td><span>#</span>{{ $user['user_id'] }} of {{ $user['user_count'] }}</td>
    </tr>
    <tr>
        <td>User flags</td>
        <td>{!! $user['user_flags'] !!}</td>
    </tr>
    <tr>
        <td>Primary currency</td>
        <td>{{ $user['primary']['code'] }}</td>
    </tr>
    <tr>
        <td>Convert to primary currency?</td>
        <td>{{ $user['convert_to_primary'] ? 'Enabled' : 'Disabled' }}</td>
    </tr>
    <tr>
        <td>Session start</td>
        <td>{{ session('start') }}</td>
    </tr>
    <tr>
        <td>Session end</td>
        <td>{{ session('end') }}</td>
    </tr>
    <tr>
        <td>View range</td>
        <td>{{ $user['view_range'] }}</td>
    </tr>
    <tr>
        <td>User language</td>
        <td>{{ $user['language'] }}</td>
    </tr>
    <tr>
        <td>User locale</td>
        <td>{{ $user['locale'] }}</td>
    </tr>
    <tr>
        <td>Locale(s) supported</td>
        <td>
            @foreach($user['locale_attempts'] as $key => $attempt)
                {{ $key }}: {{ $attempt ? ':white_check_mark:' : ':x:' }}<br>
            @endforeach
        </td>
    </tr>
    <tr>
        <td>User agent</td>
        <td>{{ $user['user_agent'] }}</td>
    </tr>
    </tbody>
</table>
