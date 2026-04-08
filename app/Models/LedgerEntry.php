<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'entry_date' => 'datetime',
        'meta' => 'array',
    ];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
