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
            background: #0f1115; color: #e6e8eb; text-align: center;
        }
        .card { max-width: 26rem; width: 90%; padding: 2rem; }
        .card img { max-height: 2.5rem; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        p { color: #9aa1ac; margin: 0; font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="card">
        @if (config('short-url.branding.logo_url'))
            <img src="{{ config('short-url.branding.logo_url') }}" alt="">
        @endif
        <h1>{{ trans('short-url::interstitials.expired_title') }}</h1>
        <p>{{ trans('short-url::interstitials.expired_description', ['site' => config('short-url.branding.site_name', config('app.name'))]) }}</p>
    </div>
</body>
</html>
