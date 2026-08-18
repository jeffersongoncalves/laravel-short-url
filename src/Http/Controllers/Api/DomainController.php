<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use JeffersonGoncalves\LaravelShortUrl\Exceptions\PlanLimitExceeded;
use JeffersonGoncalves\LaravelShortUrl\Jobs\VerifyDomainJob;
use JeffersonGoncalves\LaravelShortUrl\Models\CustomDomain;

class DomainController
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => CustomDomain::query()->paginate(50)]);
    }

    public function store(): JsonResponse
    {
        $data = request()->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:'.(new CustomDomain)->getTable().',domain'],
            'is_wildcard' => ['sometimes', 'boolean'],
            'root_redirect_url' => ['nullable', 'url'],
        ]);

        try {
            $domain = CustomDomain::query()->create($data);
        } catch (PlanLimitExceeded $e) {
            return response()->json(['error' => ['code' => 'plan_limit_exceeded', 'message' => $e->getMessage()]], 403);
        }

        VerifyDomainJob::dispatch($domain->id);

        return response()->json(['data' => $domain->fresh()], 201);
    }
}
