<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\LedgerEntry;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SupplierPayment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AccountingService
{
    public const SYSTEM_LEDGERS = [
        'cash_on_hand' => [
            'name' => 'Cash on Hand',
            'type' => 'asset',
            'description' => 'Tracks cash inflows and outflows across the POS.',
        ],
        'accounts_receivable' => [
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'description' => 'Outstanding customer balances from partial or unpaid sales.',
        ],
        'inventory_asset' => [
            'name' => 'Inventory Asset',
            'type' => 'asset',
            'description' => 'Inventory value based on received stock and sold cost.',
        ],
        'accounts_payable' => [
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'description' => 'Outstanding supplier balances for received purchase orders.',
        ],
        'sales_revenue' => [
            'name' => 'Sales Revenue',
            'type' => 'income',
            'description' => 'Net sales earned through the POS terminal.',
        ],
        'other_income' => [
            'name' => 'Other Income',
            'type' => 'income',
            'description' => 'Manual income entries outside normal sales.',
        ],
        'cost_of_goods_sold' => [
            'name' => 'Cost of Goods Sold',
            'type' => 'expense',
            'description' => 'Recognized inventory cost for sold items.',
        ],
        'operating_expenses' => [
            'name' => 'Operating Expenses',
            'type' => 'expense',
            'description' => 'Manual expense entries and overhead costs.',
        ],
        'owner_equity' => [
            'name' => 'Owner Equity',
            'type' => 'equity',
            'description' => 'Equity placeholder for future capital adjustments.',
        ],
    ];

    public function ensureDefaultLedgers(): Collection
    {
        $ledgers = collect();

        foreach (self::SYSTEM_LEDGERS as $code => $ledger) {
            $ledgers->put($code, Ledger::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $ledger['name'],
                    'type' => $ledger['type'],
                    'description' => $ledger['description'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            ));
        }

        return $ledgers;
    }

    public function recordSale(Sale $sale): void
    {
        $ledgers = $this->ensureDefaultLedgers();
        $description = 'POS sale ' . $sale->invoice_number;

        $cashAmount = round((float) $sale->paid_amount, 2);
        $receivableAmount = round((float) $sale->due_amount, 2);
        $revenueAmount = round((float) $sale->total, 2);
        $costAmount = round((float) $sale->items->sum('line_cost_total'), 2);

        if ($cashAmount > 0) {
            $this->createEntry($ledgers->get('cash_on_hand'), 'debit', $cashAmount, $sale->sold_at, [
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'reference' => $sale->invoice_number,
                'description' => $description . ' cash received',
            ]);
        }

        if ($receivableAmount > 0) {
            $this->createEntry($ledgers->get('accounts_receivable'), 'debit', $receivableAmount, $sale->sold_at, [
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'reference' => $sale->invoice_number,
                'description' => $description . ' outstanding balance',
            ]);
        }

        if ($revenueAmount > 0) {
            $this->createEntry($ledgers->get('sales_revenue'), 'credit', $revenueAmount, $sale->sold_at, [
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'reference' => $sale->invoice_number,
                'description' => $description . ' revenue recognized',
            ]);
        }

        if ($costAmount > 0) {
            $this->createEntry($ledgers->get('cost_of_goods_sold'), 'debit', $costAmount, $sale->sold_at, [
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'reference' => $sale->invoice_number,
                'description' => $description . ' inventory cost recognized',
            ]);

            $this->createEntry($ledgers->get('inventory_asset'), 'credit', $costAmount, $sale->sold_at, [
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'reference' => $sale->invoice_number,
                'description' => $description . ' inventory asset reduced',
            ]);
        }
    }

    public function recordPurchaseReceipt(PurchaseOrder $purchaseOrder): void
    {
        $ledgers = $this->ensureDefaultLedgers();
        $amount = round((float) $purchaseOrder->total_amount, 2);

        if ($amount <= 0) {
            return;
        }

        $description = 'Purchase order received ' . $purchaseOrder->po_number;

        $this->createEntry($ledgers->get('inventory_asset'), 'debit', $amount, $purchaseOrder->received_at, [
            'source_type' => PurchaseOrder::class,
            'source_id' => $purchaseOrder->id,
            'reference' => $purchaseOrder->po_number,
            'description' => $description . ' inventory recognized',
        ]);

        $this->createEntry($ledgers->get('accounts_payable'), 'credit', $amount, $purchaseOrder->received_at, [
            'source_type' => PurchaseOrder::class,
            'source_id' => $purchaseOrder->id,
            'reference' => $purchaseOrder->po_number,
            'description' => $description . ' supplier liability recognized',
        ]);
    }

    public function recordSupplierPayment(SupplierPayment $payment): void
    {
        $ledgers = $this->ensureDefaultLedgers();
        $amount = round((float) $payment->amount, 2);

        if ($amount <= 0) {
            return;
        }

        $reference = $payment->purchaseOrder?->po_number ?? ('SUP-PAY-' . $payment->id);
        $description = 'Supplier payment ' . $reference;

        $this->createEntry($ledgers->get('accounts_payable'), 'debit', $amount, $payment->paid_at, [
            'source_type' => SupplierPayment::class,
            'source_id' => $payment->id,
            'reference' => $reference,
            'description' => $description . ' liability reduced',
        ]);

        $this->createEntry($ledgers->get('cash_on_hand'), 'credit', $amount, $payment->paid_at, [
            'source_type' => SupplierPayment::class,
            'source_id' => $payment->id,
            'reference' => $reference,
            'description' => $description . ' cash paid out',
        ]);
    }

    public function recordManualTransaction(array $payload): void
    {
        $ledgers = $this->ensureDefaultLedgers();
        $ledger = Ledger::query()->findOrFail($payload['ledger_id']);
        $amount = round((float) $payload['amount'], 2);
        $entryDate = $payload['entry_date'];
        $reference = $payload['reference'] ?? null;
        $description = $payload['description'] ?? null;

        if ($payload['transaction_type'] === 'income') {
            $this->createEntry($ledgers->get('cash_on_hand'), 'debit', $amount, $entryDate, [
                'source_type' => 'manual-income',
                'reference' => $reference,
                'description' => $description ?: 'Manual income received',
            ]);

            $this->createEntry($ledger, 'credit', $amount, $entryDate, [
                'source_type' => 'manual-income',
                'reference' => $reference,
                'description' => $description ?: 'Manual income recorded',
            ]);

            return;
        }

        $this->createEntry($ledger, 'debit', $amount, $entryDate, [
            'source_type' => 'manual-expense',
            'reference' => $reference,
            'description' => $description ?: 'Manual expense recorded',
        ]);

        $this->createEntry($ledgers->get('cash_on_hand'), 'credit', $amount, $entryDate, [
            'source_type' => 'manual-expense',
            'reference' => $reference,
            'description' => $description ?: 'Manual expense paid',
        ]);
    }

    public function createEntry(
        Ledger $ledger,
        string $direction,
        float $amount,
        CarbonInterface|string|null $entryDate,
        array $attributes = []
    ): LedgerEntry {
        return LedgerEntry::query()->create([
            'ledger_id' => $ledger->id,
            'direction' => $direction,
            'amount' => round($amount, 2),
            'entry_date' => $entryDate ?: now(),
            'source_type' => $attributes['source_type'] ?? null,
            'source_id' => $attributes['source_id'] ?? null,
            'reference' => $attributes['reference'] ?? null,
            'description' => $attributes['description'] ?? null,
            'meta' => $attributes['meta'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }
}
