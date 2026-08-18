<?php

namespace JeffersonGoncalves\LaravelShortUrl\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use JeffersonGoncalves\LaravelShortUrl\Exceptions\PlanLimitExceeded;
use JeffersonGoncalves\LaravelShortUrl\Http\Resources\ShortUrlResource;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;
use JeffersonGoncalves\LaravelShortUrl\ShortUrlManager;
use Throwable;

class LinkController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->integer('per_page', 25), 100);
        $query = ShortUrl::query()->latest();

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        if ($request->filled('enabled')) {
            $query->where('is_enabled', $request->boolean('enabled'));
        }

        return ShortUrlResource::collection($query->paginate($perPage));
    }

    public function store(Request $request, ShortUrlManager $manager): JsonResponse
    {
        $data = $this->hashPassword($request->validate($this->rules()));

        try {
            return (new ShortUrlResource($manager->create($data)))->response()->setStatusCode(201);
        } catch (PlanLimitExceeded $e) {
            return response()->json(['error' => ['code' => 'plan_limit_exceeded', 'message' => $e->getMessage()]], 403);
        }
    }

    public function bulkStore(Request $request, ShortUrlManager $manager): JsonResponse
    {
        $data = $request->validate([
            'links' => ['required', 'array', 'min:1', 'max:500'],
            'links.*.destination_url' => ['required', 'url'],
            'links.*.url_key' => ['nullable', 'string', 'max:64'],
            'links.*.title' => ['nullable', 'string', 'max:255'],
        ]);

        $created = [];
        $errors = [];

        foreach ($data['links'] as $index => $link) {
            try {
                $created[] = new ShortUrlResource($manager->create($link));
            } catch (Throwable $e) {
                $errors[] = ['index' => $index, 'message' => $e->getMessage()];
            }
        }

        return response()->json(['data' => $created, 'errors' => $errors], 201);
    }

    public function show(ShortUrl $shortUrl): ShortUrlResource
    {
        return new ShortUrlResource($shortUrl);
    }

    public function update(Request $request, ShortUrl $shortUrl): ShortUrlResource
    {
        $data = $this->hashPassword($request->validate($this->rules(update: true)));
        $shortUrl->update($data);

        return new ShortUrlResource($shortUrl);
    }

    public function destroy(ShortUrl $shortUrl): JsonResponse
    {
        $shortUrl->delete();

        return response()->json(null, 204);
    }

    public function restore(ShortUrl $shortUrl): ShortUrlResource
    {
        $shortUrl->restore();

        return new ShortUrlResource($shortUrl);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(bool $update = false): array
    {
        return [
            'destination_url' => [$update ? 'sometimes' : 'required', 'url'],
            'url_key' => ['nullable', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_enabled' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'max_visits' => ['nullable', 'integer', 'min:1'],
            'password' => ['nullable', 'string', 'min:4'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hashPassword(array $data): array
    {
        if (array_key_exists('password', $data)) {
            $data['password_hash'] = $data['password'] === null ? null : Hash::make($data['password']);
            unset($data['password']);
        }

        return $data;
    }
}
