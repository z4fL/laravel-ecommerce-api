<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait InteractsWithCurrentStore
{
    protected function user(): User
    {
        return request()->user();
    }

    protected function store(): Store
    {
        return $this->user()->store;
    }

    protected function products(): HasMany
    {
        return $this->store()->products();
    }
}
