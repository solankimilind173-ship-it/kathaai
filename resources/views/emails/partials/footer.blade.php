<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #eee;">
    <tr>
        <td style="font-size: 12px; color: #666; line-height: 1.6;">
            <p style="margin: 0 0 8px 0;"><strong style="color: #b45309;">{{ config('app.name') }}</strong></p>
            <p style="margin: 0 0 8px 0;">Turn your stories into engaging videos with AI. Create, narrate, and share your narratives in minutes.</p>
            <p style="margin: 0 0 4px 0;">
                <a href="{{ rtrim(config('app.url'), '/') }}" style="color: #b45309; text-decoration: none;">Visit {{ config('app.name') }}</a>
            </p>
            <p style="margin: 12px 0 0 0; font-size: 11px; color: #999;">You received this email because you have an account or requested an action on {{ config('app.name') }}.</p>
        </td>
    </tr>
</table>
