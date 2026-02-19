<?php

namespace App\Console\Commands;

use App\Jobs\PrepareStoreParkingList;
use App\SalesmanShift;
use App\SalesmanShiftStoreDispatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegenerateLoadingSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loading-sheets:regenerate 
                            {--shift-id= : Specific shift ID to regenerate}
                            {--date= : Regenerate for specific date (Y-m-d)}
                            {--days=7 : Number of days back to regenerate (default: 7)}
                            {--all : Regenerate ALL loading sheets (use with caution)}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate loading sheets to fix duplicates and ensure accuracy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=================================================');
        $this->info('Loading Sheet Regeneration Script');
        $this->info('=================================================');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get shifts to process
        $shifts = $this->getShiftsToProcess();

        if ($shifts->isEmpty()) {
            $this->error('No shifts found matching the criteria.');
            return 1;
        }

        $this->info("Found {$shifts->count()} shifts to process:");
        $this->newLine();

        // Show summary
        $this->table(
            ['Shift ID', 'Salesman', 'Date', 'Orders', 'Current Sheets'],
            $shifts->map(function ($shift) {
                return [
                    $shift->id,
                    $shift->salesman->name ?? 'N/A',
                    $shift->start_time ? $shift->start_time->format('Y-m-d H:i') : 'N/A',
                    $shift->orders->count(),
                    $shift->dispatches->count(),
                ];
            })
        );

        $this->newLine();

        // Confirm before proceeding
        if (!$force && !$dryRun) {
            if (!$this->confirm('Do you want to proceed with regenerating these loading sheets?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Process each shift
        $this->newLine();
        $this->info('Processing shifts...');
        $this->newLine();

        $bar = $this->output->createProgressBar($shifts->count());
        $bar->start();

        $stats = [
            'processed' => 0,
            'success' => 0,
            'skipped' => 0,
            'failed' => 0,
            'sheets_deleted' => 0,
            'sheets_created' => 0,
        ];

        foreach ($shifts as $shift) {
            $bar->advance();
            
            try {
                $result = $this->processShift($shift, $dryRun);
                
                $stats['processed']++;
                if ($result['status'] === 'success') {
                    $stats['success']++;
                    $stats['sheets_deleted'] += $result['deleted'];
                    $stats['sheets_created'] += $result['created'];
                } elseif ($result['status'] === 'skipped') {
                    $stats['skipped']++;
                } else {
                    $stats['failed']++;
                }
            } catch (\Exception $e) {
                $stats['failed']++;
                $this->newLine();
                $this->error("Failed to process shift {$shift->id}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Show results
        $this->info('=================================================');
        $this->info('Results Summary');
        $this->info('=================================================');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Shifts Processed', $stats['processed']],
                ['Successful', $stats['success']],
                ['Skipped', $stats['skipped']],
                ['Failed', $stats['failed']],
                ['Sheets Deleted', $stats['sheets_deleted']],
                ['Sheets Created', $stats['sheets_created']],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN COMPLETE - No actual changes were made');
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('✅ Loading sheets regeneration complete!');
        }

        return 0;
    }

    /**
     * Get shifts to process based on options
     */
    private function getShiftsToProcess()
    {
        $query = SalesmanShift::with(['salesman', 'orders', 'dispatches'])
            ->where('status', 'close');

        // Specific shift ID
        if ($shiftId = $this->option('shift-id')) {
            return $query->where('id', $shiftId)->get();
        }

        // Specific date
        if ($date = $this->option('date')) {
            return $query->whereDate('start_time', $date)->get();
        }

        // All shifts (dangerous!)
        if ($this->option('all')) {
            $this->warn('WARNING: Processing ALL shifts in the database!');
            return $query->get();
        }

        // Default: Last N days
        $days = (int) $this->option('days');
        return $query->where('start_time', '>=', now()->subDays($days))
            ->orderBy('start_time', 'desc')
            ->get();
    }

    /**
     * Process a single shift
     */
    private function processShift(SalesmanShift $shift, bool $dryRun): array
    {
        // Check if shift has orders
        if ($shift->orders->isEmpty()) {
            return [
                'status' => 'skipped',
                'reason' => 'No orders',
                'deleted' => 0,
                'created' => 0,
            ];
        }

        // Check if salesman has location
        if (!$shift->salesman || !$shift->salesman->wa_location_and_store_id) {
            return [
                'status' => 'skipped',
                'reason' => 'No salesman location',
                'deleted' => 0,
                'created' => 0,
            ];
        }

        if ($dryRun) {
            // In dry run, just count what would be deleted/created
            $existingSheets = SalesmanShiftStoreDispatch::where('shift_id', $shift->id)->count();
            
            // Estimate new sheets (by bin locations)
            $storeId = $shift->salesman->wa_location_and_store_id;
            $itemIds = $shift->orders->flatMap(function ($order) {
                return $order->getRelatedItem->pluck('wa_inventory_item_id');
            })->unique();

            $binLocations = DB::table('wa_inventory_location_uom')
                ->whereIn('inventory_id', $itemIds)
                ->where('location_id', $storeId)
                ->distinct('uom_id')
                ->count('uom_id');

            $estimatedSheets = max(1, $binLocations); // At least 1 sheet

            return [
                'status' => 'success',
                'deleted' => $existingSheets,
                'created' => $estimatedSheets,
            ];
        }

        // Actually process the shift
        $existingSheetsCount = SalesmanShiftStoreDispatch::where('shift_id', $shift->id)->count();

        // Dispatch the job synchronously
        PrepareStoreParkingList::dispatchSync($shift);

        $newSheetsCount = SalesmanShiftStoreDispatch::where('shift_id', $shift->id)->count();

        return [
            'status' => 'success',
            'deleted' => $existingSheetsCount,
            'created' => $newSheetsCount,
        ];
    }
}
