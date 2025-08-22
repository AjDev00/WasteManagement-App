<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WasteInvoice;
use Carbon\Carbon;

class UpdateDeliveredInvoices extends Command
{
    protected $signature = 'invoices:update-delivered';
    protected $description = 'Update verified invoices to delivered when delivered_on date is reached';

    public function handle()
    {
        $now = Carbon::now();

        // Find invoices that are verified & due
        $invoices = WasteInvoice::where('status', 'verified')
            ->whereDate('delivered_on', '<=', $now)
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->status = 'delivered';
            $invoice->save();
        }

        $this->info("Updated {$invoices->count()} invoices to delivered.");
    }
}
