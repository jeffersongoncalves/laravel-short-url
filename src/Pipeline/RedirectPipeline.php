<?php

namespace JeffersonGoncalves\LaravelShortUrl\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\Response;

class RedirectPipeline
{
    /**
     * @var array<int, class-string>
     */
    protected array $stages = [
        Stages\ResolveHost::class,
        Stages\RateLimit::class,
        Stages\ResolveShortUrl::class,
        Stages\DetectBot::class,
        Stages\DetectVpnProxy::class,
        Stages\CheckAvailability::class,
        Stages\RequirePassword::class,
        Stages\ShowWarning::class,
        Stages\ResolveDestination::class,
        Stages\BuildFinalUrl::class,
        Stages\RenderInterstitial::class,
        Stages\Respond::class,
        Stages\DispatchTracking::class,
    ];

    public function handle(Request $request, string $urlKey): Response
    {
        $context = new RedirectContext($request, $urlKey);

        return app(Pipeline::class)
            ->send($context)
            ->through($this->stages)
            ->then(fn (RedirectContext $context) => $context->response);
    }
}
