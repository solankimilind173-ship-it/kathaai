{{-- Logo: ensure public/images/kathaai-logo.png exists and APP_URL is your public URL so the image loads in email clients --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px; border-bottom: 1px solid #eee;">
    <tr>
        <td style="padding: 20px 0;">
            <a href="{{ url('/') }}" style="text-decoration: none;">
                <img src="{{ asset('images/kathaai-logo.png') }}" alt="{{ config('app.name') }}" width="180" style="max-width: 180px; height: auto; display: block; border: 0;" />
            </a>
        </td>
    </tr>
</table>
