<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Lang;

class OrdersController
{
    public function index(): array
    {
        $status = 'open';

        return [
            'title' => __('messages.welcome', ['app' => config('app.name')]),
            'typo' => __('messages.welcom'),
            'empty' => trans('shop::cart.empty'),
            'checkout' => Lang::get('shop::cart.checkout'),
            'dynamic' => __("orders.status.{$status}"),
            'concatenated' => __('orders.' . $status),
            'sentence' => __('Your order is on its way'),
            'escaped' => __('It\'s here'),
            'method' => $this->trans('not.a.key'),
        ];
    }

    protected function trans(string $key): string
    {
        return $key;
    }
}
