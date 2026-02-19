<?php

namespace App\Console\Commands;

use App\SalesmanShift;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:auto-close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically close salesman shifts that are older than 24 hours or past 7 PM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $yesterday = $now->copy()->subDay();
        $sevenPmToday = $now->copy()->setTime(19, 0, 0); // 7 PM today
        
        $this->info('Starting auto-close shifts process...');
        
        // Get all open shifts
        $openShifts = SalesmanShift::where('status', 'open')->get();
        
        $closedCount = 0;
        
        foreach ($openShifts as $shift) {
            $shiftStartTime = Carbon::parse($shift->start_time);
            $shouldAutoClose = false;
            $reason = '';
            
            // Close if shift is older than 24 hours
            if ($shiftStartTime->lt($yesterday)) {
                $shouldAutoClose = true;
                $reason = 'older than 24 hours';
            }
            // Close if shift started before 7 PM yesterday and it's now past 7 PM today
            elseif ($shiftStartTime->lt($sevenPmToday->copy()->subDay()) && $now->gte($sevenPmToday)) {
                $shouldAutoClose = true;
                $reason = 'past 7 PM cutoff';
            }
            // Close if shift started today but it's now past 7 PM
            elseif ($shiftStartTime->isToday() && $now->gte($sevenPmToday)) {
                $shouldAutoClose = true;
                $reason = 'past 7 PM today';
            }
            
            if ($shouldAutoClose) {
                $shift->status = 'close';
                $shift->closed_time = $now;
                $shift->save();
                
                $closedCount++;
                
                $this->info("Closed shift #{$shift->id} for salesman #{$shift->salesman_id} - Reason: {$reason}");
                
                Log::info('Auto-closed shift', [
                    'shift_id' => $shift->id,
                    'salesman_id' => $shift->salesman_id,
                    'route_id' => $shift->route_id,
                    'start_time' => $shiftStartTime,
                    'closed_time' => $now,
                    'reason' => $reason
                ]);
            }
        }
        
        $this->info("Auto-close process completed. Closed {$closedCount} shift(s).");
        
        return 0;
    }
}
