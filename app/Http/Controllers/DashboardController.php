<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $inventorySummary = Product::query()
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->selectRaw('COUNT(products.id) as product_count')
            ->selectRaw('SUM(COALESCE(stocks.quantity, products.stock)) as total_units')
            ->selectRaw('SUM(CASE WHEN COALESCE(stocks.quantity, products.stock) <= products.low_stock_threshold THEN 1 ELSE 0 END) as low_stock_count')
            ->first();

        $todaysSales = Sale::query()
            ->where('sold_at', '>=', $today)
            ->selectRaw('COUNT(*) as invoices')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(due_amount), 0) as dues')
            ->first();

        $monthlySales = Sale::query()
            ->where('sold_at', '>=', $monthStart)
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as collected')
            ->first();

        $supplierSummary = PurchaseOrder::query()
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw('COALESCE(SUM(due_amount), 0) as supplier_due')
            ->first();

        $recentSales = Sale::query()
            ->with('customer')
            ->latest('sold_at')
            ->limit(6)
            ->get();

        $lowStockProducts = Product::query()
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.low_stock_threshold',
                DB::raw('COALESCE(stocks.quantity, products.stock) as current_stock')
            )
            ->whereRaw('COALESCE(stocks.quantity, products.stock) <= products.low_stock_threshold')
            ->orderByRaw('COALESCE(stocks.quantity, products.stock) ASC')
            ->limit(6)
            ->get();

        $recentNotifications = Notification::query()
            ->latest()
            ->limit(5)
            ->get();

        $recentActivities = AuditLog::query()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        $salesTrend = Sale::query()
            ->where('sold_at', '>=', now()->copy()->subDays(6)->startOfDay())
            ->selectRaw('DATE(sold_at) as sale_date')
            ->selectRaw('COALESCE(SUM(total), 0) as total_amount')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        $salesTrendLabels = collect(range(0, 6))
            ->map(fn ($day) => now()->copy()->subDays(6 - $day)->format('d M'))
            ->values();

        $salesTrendValues = collect(range(0, 6))
            ->map(function ($day) use ($salesTrend) {
                $dateKey = now()->copy()->subDays(6 - $day)->toDateString();
                return round((float) optional($salesTrend->get($dateKey))->total_amount, 2);
            })
            ->values();

        return view('dashboard.dashboard', [
            'stats' => [
                'products' => (int) ($inventorySummary->product_count ?? 0),
                'stock_units' => (int) ($inventorySummary->total_units ?? 0),
                'low_stock' => (int) ($inventorySummary->low_stock_count ?? 0),
                'customers' => Customer::query()->count(),
                'today_invoices' => (int) ($todaysSales->invoices ?? 0),
                'today_revenue' => round((float) ($todaysSales->revenue ?? 0), 2),
                'today_due' => round((float) ($todaysSales->dues ?? 0), 2),
                'monthly_revenue' => round((float) ($monthlySales->revenue ?? 0), 2),
                'monthly_collected' => round((float) ($monthlySales->collected ?? 0), 2),
                'supplier_due' => round((float) ($supplierSummary->supplier_due ?? 0), 2),
                'pending_purchase_orders' => (int) ($supplierSummary->pending_orders ?? 0),
                'unread_notifications' => Notification::query()->whereNull('read_at')->count(),
            ],
            'recentSales' => $recentSales,
            'lowStockProducts' => $lowStockProducts,
            'recentNotifications' => $recentNotifications,
            'recentActivities' => $recentActivities,
            'salesTrendLabels' => $salesTrendLabels,
            'salesTrendValues' => $salesTrendValues,
        ]);
    }
}
