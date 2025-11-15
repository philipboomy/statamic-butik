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
     * Provides backwards compatibility for Laravel 8.
     * In Laravel 9+, this method exists natively in the Model class.
     *
     * @return int
     */
    public function jsonOptions()
    {
        // Check if parent has this method (Laravel 9+)
        if (method_exists(get_parent_class($this), 'jsonOptions')) {
            return parent::jsonOptions();
        }

        // Default for Laravel 8
        return 0;
    }
}
