<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SettingService;
use Illuminate\Support\Facades\Gate;

class ReceiptController extends Controller
{
    public function show(Order $order, SettingService $settings)
    {
        Gate::authorize('order.view', $order);

        return view('receipts.show', [
            'order' => $order->load(['items', 'payment', 'user']),
            'outlet' => [
                'name' => $settings->get('outlet_name', 'MyKasir'),
                'address' => $settings->get('outlet_address'),
                'phone' => $settings->get('outlet_phone'),
                'footer' => $settings->get('receipt_footer'),
                'tax_enabled' => (bool) $settings->get('tax_enabled', false),
                'tax_rate_bp' => (int) $settings->get('tax_rate_bp', 0),
                'service_rate_bp' => (int) $settings->get('service_rate_bp', 0),
            ],
        ]);
    }
}
