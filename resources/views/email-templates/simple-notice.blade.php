<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Mastoo' }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:24px;">
<div style="max-width:560px;margin:0 auto;background:#fff;padding:24px;border-radius:8px;">
    <h2 style="margin-top:0;">{{ $title ?? 'Mastoo' }}</h2>
    <p>{{ $body ?? '' }}</p>
</div>
</body>
</html>
