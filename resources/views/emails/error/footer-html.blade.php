<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
    {{ trans('email.closing') }}
</p>
<p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:13px;">
    {{ trans('email.signature') }}
</p>

@if('' !== $ip)
    <p style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size:11px;color:#aaa;">
        {{ trans('email.footer_ps', ['ipAddress' => $ip]) }}
    </p>
@endif

</body>
</html>
