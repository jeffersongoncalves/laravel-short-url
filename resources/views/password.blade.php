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
            max-width: 26rem; width: 90%; padding: 2rem;
            background: #171a21; border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        .card img { max-height: 2.5rem; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        p { color: #9aa1ac; margin: 0 0 1.25rem; font-size: 0.9rem; }
        input[type="password"] {
            width: 100%; padding: 0.65rem 0.75rem; border-radius: 0.5rem;
            border: 1px solid #2a2f3a; background: #0f1115; color: #e6e8eb;
            box-sizing: border-box; font-size: 1rem; margin-bottom: 0.75rem;
        }
        button {
            width: 100%; padding: 0.65rem 0.75rem; border-radius: 0.5rem;
            border: none; background: #3b82f6; color: white;
            font-size: 1rem; cursor: pointer;
        }
        .error { color: #f87171; font-size: 0.85rem; margin-bottom: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        @if (config('short-url.branding.logo_url'))
            <img src="{{ config('short-url.branding.logo_url') }}" alt="">
        @endif
        <h1>{{ trans('short-url::interstitials.password_title') }}</h1>
        <p>{{ $hint ?: trans('short-url::interstitials.password_description') }}</p>

        @if (isset($errors) && $errors->any())
            <div class="error">{{ $errors->first('password') }}</div>
        @endif

        <form method="POST" action="{{ route('short-url.unlock', $urlKey) }}">
            @csrf
            <input type="password" name="password" autofocus required>
            <button type="submit">{{ trans('short-url::interstitials.unlock') }}</button>
        </form>
    </div>
</body>
</html>
