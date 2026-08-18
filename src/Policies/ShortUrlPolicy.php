<?php

namespace JeffersonGoncalves\LaravelShortUrl\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use JeffersonGoncalves\LaravelShortUrl\Models\ShortUrl;

class ShortUrlPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        return true;
    }

    public function delete(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        return true;
    }

    public function deleteAny(Authenticatable $user): bool
    {
        return true;
    }
}
