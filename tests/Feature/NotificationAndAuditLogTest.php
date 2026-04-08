<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAndAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_creation_generates_low_stock_notification_and_audit_log(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Beverages',
        ]);

        $response = $this->actingAs($user)->postJson(route('products.store'), [
            'name' => 'Cola',
            'sku' => 'COLA-001',
            'category_id' => $category->id,
            'price' => 100,
            'cost_price' => 70,
            'stock' => 3,
            'low_stock_threshold' => 5,
            'status' => 1,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'type' => 'low_stock',
            'notification_key' => 'low-stock-product-1',
            'title' => 'Low stock alert',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'products',
            'action' => 'created',
            'user_id' => $user->id,
        ]);
    }

    public function test_sale_creation_generates_order_and_payment_notifications_and_audit_log(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Groceries',
        ]);
        $product = Product::query()->create([
            'name' => 'Rice Bag',
            'sku' => 'RICE-001',
            'category_id' => $category->id,
            'price' => 500,
            'cost_price' => 350,
            'stock' => 8,
            'low_stock_threshold' => 5,
            'inventory_locked' => true,
            'status' => true,
        ]);

        Stock::query()->create([
            'product_id' => $product->id,
            'quantity' => 8,
        ]);

        $response = $this->actingAs($user)->postJson(route('sales.store'), [
            'customer_id' => null,
            'promotion_id' => null,
            'manual_discount' => 0,
            'tax_rate' => 0,
            'notes' => 'Partial payment sale',
            'cart' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                ],
            ],
            'payments' => [
                [
                    'method' => 'cash',
                    'amount' => 1000,
                    'reference' => 'CASH-1',
                    'note' => '',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('notifications', [
            'type' => 'new_order',
            'notification_key' => 'sale-order-1',
        ]);
        $this->assertDatabaseHas('notifications', [
            'type' => 'payment_reminder',
            'notification_key' => 'sale-due-1',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'sales',
            'action' => 'created',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'inventory',
            'action' => 'sale_stock_deducted',
            'user_id' => $user->id,
        ]);
    }

    public function test_notifications_can_be_listed_and_marked_as_read(): void
    {
        $user = User::factory()->create();

        Notification::query()->create([
            'type' => 'payment_reminder',
            'notification_key' => 'manual-reminder-1',
            'level' => 'danger',
            'title' => 'Payment reminder',
            'message' => 'Outstanding invoice reminder',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.list'))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Payment reminder');

        $notification = Notification::query()->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('notifications.markRead', $notification))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
