<?php

namespace Jonassiewertsen\StatamicButik\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Jonassiewertsen\StatamicButik\Http\Traits\MoneyTrait;

abstract class ButikModel extends Model
{
    use MoneyTrait;

    /**
     * The route to the base shop.
     */
    protected static function shopRoute()
    {
        return config('butik.route_shop-prefix');
    }

    /**
     * Get the JSON encoding options for the model.
     * This method provides Laravel 9+ compatibility for Laravel 8.
     *
     * @return int
     */
    public function jsonOptions()
    {
        return 0;
    }
}
