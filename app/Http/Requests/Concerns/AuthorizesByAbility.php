<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Facades\Gate;

trait AuthorizesByAbility
{
    public function authorize(): bool
    {
        return Gate::allows($this->ability);
    }
}
