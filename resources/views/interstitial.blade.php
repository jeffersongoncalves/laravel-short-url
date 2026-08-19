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
        .card { max-width: 24rem; width: 90%; padding: 2rem; text-align: center; }
        .bar { height: 3px; background: #2a2f3a; border-radius: 2px; overflow: hidden; margin-top: 1.5rem; }
        .bar-fill { height: 100%; width: 0%; background: #3b82f6; transition: width 250ms linear; }
        .consent { margin-top: 1.5rem; }
        .consent button {
            padding: 0.5rem 1.25rem; border-radius: 0.5rem; border: none;
            font-size: 0.9rem; cursor: pointer; margin: 0 0.25rem;
        }
        .consent .accept { background: #3b82f6; color: white; }
        .consent .decline { background: #2a2f3a; color: #e6e8eb; }
    </style>
</head>
<body>
    <div class="card">
        <p>{{ trans('short-url::interstitials.redirecting') }}</p>

        @if ($requireConsent)
            <div id="consent" class="consent">
                <button class="accept" onclick="shortUrlProceed(true)">{{ trans('short-url::interstitials.consent_accept') }}</button>
                <button class="decline" onclick="shortUrlProceed(false)">{{ trans('short-url::interstitials.consent_decline') }}</button>
            </div>
        @else
            <div class="bar"><div class="bar-fill" id="bar"></div></div>
        @endif
    </div>

    <script>
        var destinationUrl = @json($destinationUrl, JSON_UNESCAPED_SLASHES);
        var pixelScripts = @json($pixelScripts, JSON_UNESCAPED_SLASHES);

        function shortUrlRun(withPixels) {
            if (withPixels) {
                pixelScripts.forEach(function (script) {
                    try { (new Function(script))(); } catch (e) {}
                });
            }

            window.location.replace(destinationUrl);
        }

        function shortUrlProceed(accepted) {
            document.getElementById('consent').style.display = 'none';
            shortUrlRun(accepted);
        }

        @if (! $requireConsent)
            requestAnimationFrame(function () {
                document.getElementById('bar').style.width = '100%';
            });
            setTimeout(function () { shortUrlRun(true); }, 250);
        @endif
    </script>
</body>
</html>
