<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name', 'ERP') }} | Scheduled Maintenance</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, Helvetica, sans-serif;
        }
        * {
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            color: #1f2937;
            background: linear-gradient(135deg, #eef6f7 0%, #f8fafc 55%, #e8eef5 100%);
        }
        main {
            width: min(640px, 100%);
            padding: 48px;
            text-align: center;
            background: #ffffff;
            border: 1px solid #dbe4ea;
            border-radius: 18px;
            box-shadow: 0 24px 60px rgba(22, 50, 79, 0.12);
        }
        .icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 22px;
            display: grid;
            place-items: center;
            color: #ffffff;
            font-size: 34px;
            border-radius: 50%;
            background: #168a96;
        }
        h1 {
            margin: 0 0 14px;
            color: #16324f;
            font-size: clamp(28px, 5vw, 40px);
        }
        p {
            margin: 10px 0;
            color: #52616b;
            font-size: 17px;
            line-height: 1.6;
        }
        .status {
            display: inline-block;
            margin-top: 22px;
            padding: 8px 14px;
            color: #166534;
            font-size: 14px;
            font-weight: 700;
            background: #e7f7f0;
            border-radius: 999px;
        }
    </style>
</head>
<body>
    <main>
        <div class="icon" aria-hidden="true">&#9881;</div>
        <h1>Scheduled Maintenance</h1>
        <p>{{ config('app.name', 'The ERP') }} is temporarily unavailable while essential maintenance is completed.</p>
        <p>No action is required. Please try again shortly.</p>
        @if (! empty($retryAfter))
            <div class="status">Suggested retry: {{ $retryAfter }} seconds</div>
        @else
            <div class="status">Service will return soon</div>
        @endif
    </main>
</body>
</html>
