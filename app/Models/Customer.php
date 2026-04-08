<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'total_spent' => 'decimal:2',
        'last_purchase_at' => 'datetime',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
