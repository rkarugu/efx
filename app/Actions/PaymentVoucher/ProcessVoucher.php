<?php

namespace App\Actions\PaymentVoucher;

use App\Model\WaAccountingPeriod;
use App\Model\WaBanktran;
use App\Model\WaChartsOfAccount;
use App\Model\WaCompanyPreference;
use App\Model\WaGlTran;
use App\Model\WaNumerSeriesCode;
use App\Model\WaSuppTran;
use App\PaymentVoucher;

class ProcessVoucher
{
    public function process(PaymentVoucher $voucher)
    {
        $bank_account = $voucher->account;
        $accountuingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
        $supplier = $voucher->supplier;

        foreach ($voucher->voucherItems as $item) {
            if ($item->payable_type == 'advance') {
                $item->payable->update([
                    'status' => 'Paid',
                    'paid_at' => now()
                ]);

                break;
            }

            $balance = $item->payable->total_amount_inc_vat - $item->payable->allocated_amount - $item->payable->withholding_amount;
            if ($item->amount >= $balance) {
                $item->payable->update([
                    'settled' => true
                ]);
            }
        }

        $series_module = WaNumerSeriesCode::where('module', 'CREDITORS_PAYMENT')->first();

        $newSupplierTrans = new WaSuppTran();
        $newSupplierTrans->document_no = $voucher->number;
        $newSupplierTrans->trans_date = $voucher->created_at;
        $newSupplierTrans->suppreference = $voucher->number;
        $newSupplierTrans->supplier_no = $supplier->supplier_code;
        $newSupplierTrans->grn_type_number = $series_module->type_number;
        $newSupplierTrans->prepared_by = getLoggeduserProfile()->id;
        $newSupplierTrans->total_amount_inc_vat = $voucher->amount * -1;
        $newSupplierTrans->save();

        $dateTime = $newSupplierTrans->created_at;

        $btran = new WaBanktran();
        $btran->type_number = $series_module->type_number;
        $btran->document_no = $voucher->number;
        $btran->bank_gl_account_code = $bank_account->getGlDetail->account_code;
        $btran->reference = $voucher->number;
        $btran->trans_date = $dateTime;
        $btran->wa_payment_method_id = $voucher->wa_payment_method_id;
        $btran->amount = $voucher->amount * -1;
        $btran->wa_curreny_id = 1;
        $btran->save();

        // Debit Creditors
        $dr = new WaGlTran();
        $dr->period_number = $accountuingPeriod ? $accountuingPeriod->period_no : null;
        $dr->wa_supp_tran_id = $newSupplierTrans->id;
        $dr->supplier_account_number = $newSupplierTrans->supplier_no;
        $dr->grn_type_number = $series_module->type_number;
        $dr->trans_date = $dateTime;
        $dr->grn_last_used_number = $series_module->last_number_used;
        $dr->transaction_type = $series_module->description;
        $dr->transaction_no = $voucher->number;
        $dr->reference = $voucher->number;
        $dr->restaurant_id = getLoggeduserProfile()->restaurant_id;
        $dr->tb_reporting_branch = getLoggeduserProfile()->restaurant_id;
        $dr->narrative = $voucher->narrative;
        $companyPreference = WaCompanyPreference::where('id', '1')->first();
        $dr->account = $companyPreference->creditorControlGlAccount->account_code;
        $dr->amount = $voucher->amount;
        $dr->save();

        // Credit Bank
        $cr = new WaGlTran();
        $cr->period_number = $accountuingPeriod ? $accountuingPeriod->period_no : null;
        $cr->wa_supp_tran_id = $newSupplierTrans->id;
        $cr->supplier_account_number = $newSupplierTrans->supplier_no;
        $cr->grn_type_number = $series_module->type_number;
        $cr->trans_date = $dateTime;
        $cr->restaurant_id = getLoggeduserProfile()->restaurant_id;
        $cr->tb_reporting_branch = getLoggeduserProfile()->restaurant_id;
        $cr->grn_last_used_number = $series_module->last_number_used;
        $cr->transaction_type = $series_module->description;
        $cr->transaction_no = $voucher->number;
        $cr->reference = $voucher->number;
        $cr->narrative = $voucher->narrative;
        $cr->account = $bank_account->getGlDetail->account_code;
        $cr->amount = $voucher->amount * -1;
        $cr->save();

        if ($voucher->withholding_amount > 0) {
            $transaction = new WaSuppTran();
            $transaction->document_no = $voucher->number;
            $transaction->trans_date = $voucher->created_at;
            $transaction->suppreference = $voucher->number;
            $transaction->supplier_no = $voucher->supplier->supplier_code;
            $transaction->grn_type_number = $series_module->type_number;
            $transaction->prepared_by = getLoggeduserProfile()->id;
            $transaction->total_amount_inc_vat =  $voucher->withholding_amount * -1;
            $transaction->save();

            // DEBIT Creditors
            $dr = new WaGlTran();
            $dr->period_number = $accountuingPeriod ? $accountuingPeriod->period_no : null;
            $dr->wa_supp_tran_id = $transaction->id;
            $dr->grn_type_number = $series_module->type_number;
            $dr->trans_date = $voucher->created_at;
            $dr->grn_last_used_number = $series_module->last_number_used;
            $dr->transaction_type = $series_module->description;
            $dr->transaction_no = $voucher->number;
            $dr->reference = $voucher->number;
            $dr->restaurant_id = getLoggeduserProfile()->restaurant_id;
            $dr->tb_reporting_branch = getLoggeduserProfile()->restaurant_id;
            $dr->narrative = "Withholding Tax  - $voucher->number";
            $dr->supplier_account_number =  $voucher->supplier->supplier_code;
            $companyPreference = WaCompanyPreference::where('id', '1')->first();
            $dr->account = $companyPreference->creditorControlGlAccount->account_code;
            $dr->amount = $voucher->withholding_amount;
            $dr->save();

            // CREDIT Withholding
            $cr = new WaGlTran();
            $cr->period_number = $accountuingPeriod ? $accountuingPeriod->period_no : null;
            $cr->wa_supp_tran_id = $transaction->id;
            $cr->supplier_account_number = $transaction->supplier_no;
            $cr->grn_type_number = $series_module->type_number;
            $cr->trans_date = $dateTime;
            $cr->grn_last_used_number = $series_module->last_number_used;
            $cr->transaction_type = $series_module->description;
            $cr->transaction_no = $voucher->number;
            $cr->reference = $voucher->number;
            $cr->restaurant_id = getLoggeduserProfile()->restaurant_id;
            $cr->tb_reporting_branch = getLoggeduserProfile()->restaurant_id;
            $cr->narrative = "Withholding Tax  - $voucher->number";
            $glAccount = WaChartsOfAccount::where('account_name', 'LIKE', '%withholding%')->first();
            $cr->account = $glAccount->account_code;
            $cr->amount = $voucher->withholding_amount * -1;
            $cr->save();
        }

        $voucher->update([
            'status' => PaymentVoucher::PROCESSED,
        ]);
    }
}
