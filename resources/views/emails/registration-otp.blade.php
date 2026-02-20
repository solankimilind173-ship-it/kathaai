<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification code</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    @include('emails.partials.header')
    <h1 style="color: #b45309;">Verify your email</h1>
    @if($name)
        <p>Hi {{ $name }},</p>
    @endif
    <p>Use this code to complete your registration:</p>
    <p style="font-size: 28px; font-weight: bold; letter-spacing: 8px; color: #b45309;">{{ $otp }}</p>
    <p>This code expires in 10 minutes.</p>
    <p style="margin-top: 30px; font-size: 12px; color: #666;">If you did not request this code, you can ignore this email.</p>
    @include('emails.partials.footer')
</body>
</html>
