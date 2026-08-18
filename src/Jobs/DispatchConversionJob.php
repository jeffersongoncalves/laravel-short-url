<?php

namespace JeffersonGoncalves\LaravelShortUrl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\LaravelShortUrl\Contracts\ConversionApiDispatcher;
use JeffersonGoncalves\LaravelShortUrl\Models\Conversion;
use Throwable;

class DispatchConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $conversionId) {}

    public function handle(ConversionApiDispatcher $dispatcher): void
    {
        try {
            $conversion = Conversion::query()->find($this->conversionId);

            if ($conversion) {
                $dispatcher->send($conversion);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
