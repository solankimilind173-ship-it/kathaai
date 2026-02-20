<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password changed</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    @include('emails.partials.header')
    <h1 style="color: #b45309;">Password changed</h1>
    <p>Hi {{ $user->name }},</p>
    <p>Your password for {{ config('app.name') }} was changed successfully.</p>
    @if($location || $deviceId)
    <p style="margin-top: 16px; padding: 12px; background: #f5f5f5; border-radius: 6px; font-size: 14px;">
        <strong>Details of this change:</strong><br>
        @if($location) Location: {{ $location }}<br> @endif
        @if($deviceId) Device ID: {{ $deviceId }} @endif
    </p>
    @endif
    <p style="margin-top: 24px;">If you did not make this change, please reset your password immediately and contact support.</p>
    <p>
        <a href="{{ url('/forgot-password') }}" style="display: inline-block; padding: 10px 20px; background: #b45309; color: #fff; text-decoration: none; border-radius: 6px;">Reset password</a>
    </p>
    @include('emails.partials.footer')
</body>
</html>
