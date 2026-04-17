<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class RestaurantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('restaurant')) {
            $builder->where($model->getTable() . '.restaurant_id', app('restaurant')->id);
        }
    }
}
