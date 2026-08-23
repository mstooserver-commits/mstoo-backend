<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MSTOO — Maintenance</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: Inter, system-ui, sans-serif; background: #0f172a; color: #e2e8f0; }
        .card { max-width: 520px; padding: 40px 32px; text-align: center; }
        h1 { font-size: 28px; margin: 0 0 12px; }
        p { color: #94a3b8; line-height: 1.6; }
        .brand { letter-spacing: .2em; font-size: 12px; color: #38bdf8; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">MSTOO</div>
        <h1>We’ll be back shortly</h1>
        <p>{{ $message }}</p>
        @if(!empty($start_at) || !empty($end_at))
            <p>
                @if(!empty($start_at)) Start: {{ $start_at }}<br>@endif
                @if(!empty($end_at)) End: {{ $end_at }}@endif
            </p>
        @endif
    </div>
</body>
</html>
