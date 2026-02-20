<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #b45309;">Welcome to {{ config('app.name') }}</h1>
    <p>Hi {{ $user->name }},</p>
    <p>Thanks for registering. You can now sign in and start creating your story projects.</p>
    <p>
        <a href="{{ url('/login') }}" style="display: inline-block; padding: 10px 20px; background: #b45309; color: #fff; text-decoration: none; border-radius: 6px;">Sign in</a>
    </p>
    <p style="margin-top: 30px; font-size: 12px; color: #666;">If you did not create an account, you can ignore this email.</p>
</body>
</html>
