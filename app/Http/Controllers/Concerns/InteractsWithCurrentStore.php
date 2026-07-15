<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait InteractsWithCurrentStore
{
    protected function currentUser(): User
    {
        return request()->user();
    }

    protected function currentStore(): Store
    {
        return $this->currentUser()->store;
    }

    protected function currentStoreProducts(): HasMany
    {
        return $this->currentStore()->products();
    }
}
