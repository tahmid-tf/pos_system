<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('address');
            $table->unsignedInteger('loyalty_points')->default(0)->after('is_active');
            $table->decimal('total_spent', 12, 2)->default(0)->after('loyalty_points');
            $table->timestamp('last_purchase_at')->nullable()->after('total_spent');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'loyalty_points',
                'total_spent',
                'last_purchase_at',
            ]);
        });
    }
};
