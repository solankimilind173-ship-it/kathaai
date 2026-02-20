<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project step completed</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #b45309;">Project step completed</h1>
    <p>Hi {{ $user->name }},</p>
    <p><strong>{{ $stepName }}</strong> has been completed for your project <strong>{{ $project->title }}</strong>.</p>
    <p>{{ $stepDescription }}</p>
    <p>
        <a href="{{ url('/projects/' . $project->id) }}" style="display: inline-block; padding: 10px 20px; background: #b45309; color: #fff; text-decoration: none; border-radius: 6px;">View project</a>
    </p>
</body>
</html>
