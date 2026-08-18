<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('short-url.branding.site_name', config('app.name')) }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0;
            background: #0f1115; color: #e6e8eb;
        }
        .card {
            max-width: 28rem; width: 90%; padding: 2rem;
            background: #171a21; border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        .card img { max-height: 2.5rem; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        p { color: #9aa1ac; margin: 0 0 1.25rem; font-size: 0.9rem; word-break: break-all; }
        a.continue {
            display: inline-block; padding: 0.65rem 1.25rem; border-radius: 0.5rem;
            background: #3b82f6; color: white; text-decoration: none; font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="card">
        @if (config('short-url.branding.logo_url'))
            <img src="{{ config('short-url.branding.logo_url') }}" alt="">
        @endif
        <h1>{{ trans('short-url::interstitials.warning_title') }}</h1>
        <p>{{ $message ?: trans('short-url::interstitials.warning_description', ['url' => $destinationUrl]) }}</p>
        <a class="continue" href="{{ $continueUrl }}">{{ trans('short-url::interstitials.continue') }}</a>
    </div>
</body>
</html>
