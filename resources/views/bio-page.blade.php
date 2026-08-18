<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->og_title ?? $page->title ?? $page->handle }}</title>
    @if ($page->og_description)
        <meta property="og:description" content="{{ $page->og_description }}">
    @endif
    @if ($page->og_image_path)
        <meta property="og:image" content="{{ $page->og_image_path }}">
    @endif
    <style>
        :root { color-scheme: light dark; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex; flex-direction: column; align-items: center;
            min-height: 100vh; margin: 0; padding: 2rem 1rem;
            background: #0f1115; color: #e6e8eb;
        }
        .avatar { width: 5rem; height: 5rem; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.25rem; }
        .bio { color: #9aa1ac; text-align: center; max-width: 24rem; margin: 0 0 1.5rem; font-size: 0.9rem; }
        .links { width: 100%; max-width: 24rem; display: flex; flex-direction: column; gap: 0.75rem; }
        .block {
            display: block; padding: 0.85rem 1.25rem; border-radius: 0.75rem;
            background: #171a21; color: #e6e8eb; text-decoration: none;
            text-align: center; font-size: 0.95rem;
        }
        .block img, .block video { max-width: 100%; border-radius: 0.5rem; }
    </style>
</head>
<body>
    @if ($page->avatar_path)
        <img class="avatar" src="{{ $page->avatar_path }}" alt="">
    @endif

    <h1>{{ $page->title ?? $page->handle }}</h1>

    @if ($page->bio)
        <p class="bio">{{ $page->bio }}</p>
    @endif

    <div class="links">
        @foreach ($links as $link)
            @switch($link->type)
                @case('text')
                    <div class="block">{{ $link->content['body'] ?? '' }}</div>
                    @break
                @case('image')
                    <div class="block"><img src="{{ $link->content['url'] ?? '' }}" alt="{{ $link->label }}"></div>
                    @break
                @case('video')
                    <div class="block"><video src="{{ $link->content['url'] ?? '' }}" controls></video></div>
                    @break
                @default
                    <a class="block" href="{{ url("bio/{$page->handle}/l/{$link->id}") }}">{{ $link->label }}</a>
            @endswitch
        @endforeach
    </div>
</body>
</html>
