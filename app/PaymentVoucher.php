<?php

namespace App;

use App\Model\User;
use App\Model\WaBankAccount;
use App\Model\WaSupplier;
use App\Models\GlReconStatement;
use App\Models\WaBankFileItem;
use App\Models\WaPaymentMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PaymentVoucher extends Model
{
    use HasFactory;

    const PENDING = 0;

    const APPROVED = 1;

    const PROCESSED = 2;

    protected $guarded = [];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    protected $appends = [
        'remittanceUrl',
        'withholdingAmount'
    ];

    public function scopePending($query)
    {
        return $query->where('status', self::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::APPROVED);
    }

    public function scopeProcessing($query)
    {
        return $query->whereIn('status', [self::PENDING, self::APPROVED]);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', self::PROCESSED);
    }

    public function isPending()
    {
        return $this->status == self::PENDING;
    }

    public function isApproved()
    {
        return $this->status == self::APPROVED;
    }

    public function isProcessed()
    {
        return  $this->status == self::PROCESSED;
    }

    public function supplier()
    {
        return $this->belongsTo(WaSupplier::class, 'wa_supplier_id');
    }

    public function account()
    {
        return $this->belongsTo(WaBankAccount::class, 'wa_bank_account_id');
    }

    public function paymentMode()
    {
        return $this->belongsTo(WaPaymentMode::class, 'wa_payment_mode_id');
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function bankFileItem()
    {
        return $this->hasOne(WaBankFileItem::class, 'payment_voucher_id');
    }

    public function voucherItems()
    {
        return $this->hasMany(PaymentVoucherItem::class, 'payment_voucher_id');
    }

    public function cheques()
    {
        return $this->hasMany(PaymentVoucherCheque::class, 'payment_voucher_id');
    }

    public function getRemittanceUrlAttribute()
    {
        return route('payment-vouchers.print_remittance', $this->id);
    }

    public function isAdvancePayment()
    {
        return $this->voucherItems()->where('payable_type', 'advance')->exists();
    }

    public function isBillPayment()
    {
        return $this->voucherItems()->where('payable_type', 'bill')->exists();
    }

    public function getWithholdingAmountAttribute()
    {
        return $this->voucherItems->sum(function ($item) {
            return $item->payable?->withholding_amount + $item->debitNotes->sum('withholding_amount') - $item->creditNotes->sum('withholding_amount');
        });
    }

    public function glMatched(): MorphOne
    {
        return $this->morphOne(GlReconStatement::class, 'matched');
    }
}
