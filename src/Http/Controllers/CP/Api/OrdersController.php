<?php

namespace Jonassiewertsen\StatamicButik\Http\Controllers\CP\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Jonassiewertsen\StatamicButik\Http\Models\Order;
use Jonassiewertsen\StatamicButik\Http\Resources\OrderResource;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;

class OrdersController
{
    use QueriesFilters;

    /**
     * The requested orders will be returned, respecting any typed search, order or pagination.
     */
    public function index(FilteredRequest $request): OrderResource
    {
        $query = $this->fetchOrders();

        $activeFilterBadges = $this->queryFilters($query, $request->filters);

        $orders = $this->sortByCustomerField($query->get());

        $orders = $this->paginatedOrders($orders);

        return (new OrderResource($orders))
            ->columnPreferenceKey('butik.orders.columns')
            ->additional(['meta' => [
                'activeFilterBadges' => $activeFilterBadges,
            ]]);
    }

    /**
     * Fetching all orders with default ordering and sorting, in case none will be passed.
     * With no search query present, the search will be ignored.
     */
    private function fetchOrders(): Builder
    {
        $sortOrder = $this->sortOrder();
        $sortBy = request('sort') ?? 'created_at';

        // Columns like "name" and "email" are derived from the customer JSON and don't
        // exist in the table, so fall back to created_at and sort those in PHP later.
        if (! in_array($sortBy, self::SORTABLE_COLUMNS, true)) {
            $sortBy = 'created_at';
        }

        $query = request()->filled('search') ? $this->searchOrders() : Order::query();

        return $query->orderBy($sortBy, $sortOrder);
    }

    private const SORTABLE_COLUMNS = [
        'id', 'number', 'status', 'method', 'total_amount',
        'created_at', 'paid_at', 'failed_at', 'canceled_at', 'shipped_at', 'completed_at',
    ];

    private function sortOrder(): string
    {
        return strtolower((string) request('order')) === 'asc' ? 'asc' : 'desc';
    }

    /**
     * Sort by fields that live inside the customer JSON (not real table columns).
     */
    private function sortByCustomerField($orders)
    {
        $sortBy = request('sort');

        if (! in_array($sortBy, ['name', 'email'], true)) {
            return $orders;
        }

        $sorted = $orders->sortBy(function ($order) use ($sortBy) {
            $customer = $order->customer;

            $value = $sortBy === 'name'
                ? ($customer->firstname ?? '').' '.($customer->surname ?? '')
                : ($customer->email ?? '');

            return mb_strtolower(trim($value));
        }, SORT_NATURAL, $this->sortOrder() === 'desc');

        return $sorted->values();
    }

    /**
     * Searching the orders, we will respect all existing customer information. On top
     * of that the following fields are searchable:
     * id, number, status, total amount and the date field.
     */
    private function searchOrders(): Builder
    {
        $search = request('search');
        $searchTerm = "%{$search}%";

        return Order::where(function ($query) use ($searchTerm) {
            $query->where('customer', 'like', $searchTerm)
                ->orWhere('id', 'like', $searchTerm)
                ->orWhere('number', 'like', $searchTerm)
                ->orWhere('status', 'like', $searchTerm)
                ->orWhere('total_amount', 'like', $searchTerm)
                ->orWhere('created_at', 'like', $searchTerm);
        });
    }

    /**
     * Paginating the orders to only return those, which are within the
     * range the user wants displayed.
     */
    private function paginatedOrders($orders): LengthAwarePaginator
    {
        $perPage = request('perPage') ?? config('statamic.cp.pagination_size');
        $currentPage = (int) request('page') ?? 0;
        $offset = ($currentPage - 1) * $perPage;
        $totalCount = $orders->count();

        $orders = $orders->slice($offset, $perPage);

        return new LengthAwarePaginator($orders, $totalCount, $perPage, $currentPage);
    }
}
