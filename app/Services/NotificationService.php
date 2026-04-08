<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;

class NotificationService
{
    public function create(array $attributes): Notification
    {
        $payload = [
            'type' => $attributes['type'],
            'notification_key' => $attributes['notification_key'] ?? null,
            'level' => $attributes['level'] ?? 'info',
            'title' => $attributes['title'],
            'message' => $attributes['message'],
            'data' => $attributes['data'] ?? null,
            'created_by' => auth()->id(),
            'read_at' => $attributes['read_at'] ?? null,
        ];

        if (!empty($payload['notification_key'])) {
            $notification = Notification::query()->firstOrNew([
                'notification_key' => $payload['notification_key'],
            ]);

            $notification->fill($payload);
            $notification->read_at = null;
            $notification->save();

            return $notification;
        }

        return Notification::query()->create($payload);
    }

    public function createLowStockAlert(Product $product, int $currentStock): Notification
    {
        return $this->create([
            'type' => 'low_stock',
            'notification_key' => 'low-stock-product-' . $product->id,
            'level' => 'warning',
            'title' => 'Low stock alert',
            'message' => $product->name . ' is at ' . $currentStock . ' units, below the threshold of '
                . (int) $product->low_stock_threshold . '.',
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $currentStock,
                'threshold' => (int) $product->low_stock_threshold,
            ],
        ]);
    }

    public function resolveLowStockAlert(Product $product): void
    {
        Notification::query()
            ->where('notification_key', 'low-stock-product-' . $product->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function createSaleAlert(Sale $sale): Notification
    {
        return $this->create([
            'type' => 'new_order',
            'notification_key' => 'sale-order-' . $sale->id,
            'level' => 'info',
            'title' => 'New sale recorded',
            'message' => $sale->invoice_number . ' was created for ' . number_format((float) $sale->total, 2) . '.',
            'data' => [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => (float) $sale->total,
                'customer_name' => $sale->customer?->name ?? 'Walk-in Customer',
            ],
        ]);
    }

    public function createPurchaseOrderAlert(PurchaseOrder $purchaseOrder): Notification
    {
        return $this->create([
            'type' => 'new_order',
            'notification_key' => 'purchase-order-' . $purchaseOrder->id,
            'level' => 'info',
            'title' => 'New purchase order created',
            'message' => $purchaseOrder->po_number . ' was created for '
                . ($purchaseOrder->supplier?->name ?? 'a supplier') . '.',
            'data' => [
                'purchase_order_id' => $purchaseOrder->id,
                'po_number' => $purchaseOrder->po_number,
                'supplier_name' => $purchaseOrder->supplier?->name,
                'total_amount' => (float) $purchaseOrder->total_amount,
            ],
        ]);
    }

    public function createPaymentReminder(
        string $key,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return $this->create([
            'type' => 'payment_reminder',
            'notification_key' => $key,
            'level' => 'danger',
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function resolvePaymentReminder(string $key): void
    {
        Notification::query()
            ->where('notification_key', $key)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
