<?php

namespace App\Console;

use App\Console\Commands\GetUnbalancedInvoices;
use App\Console\Commands\PettyCashRequestNotification;
use App\Console\Commands\RunEndOfDayChecks;
use App\Console\Commands\RunSalesManPerfomance;
use App\Console\Commands\UnbalancedTrailBalanceChecker;
use App\Jobs\UnbalancedCompletedInvoices;
use App\Models\ScheduledNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendShiftAutoCloseReminder::class,
        Commands\AutoCloseSalesmanShifts::class,
        Commands\AutoCloseShifts::class,
        Commands\CreateShifts::class,
        Commands\Utilities\UpdateTenderEntries::class,
        Commands\Utilities\UpdateOrderToShowMissingGrns::class,
        Commands\Utilities\AddWithholdingToNotes::class,
        Commands\Utilities\CreateInvoicesForPastSuppTrans::class,
        Commands\Utilities\FixSupplierInvoiceBalances::class,
        Commands\Utilities\CorrectWitholdingAmounts::class,
        Commands\Utilities\CopyLocationStockStatus::class,
        Commands\Utilities\CalculateTotalLpoDiscount::class,
        Commands\CreateDailyStockCountSheet::class,
        Commands\SendScheduledNotifications::class,
        Commands\RunReportsSeeders::class,
        Commands\FlashSalesDisable::class,
        Commands\PosCashSales\SendStaleOrderNotification::class,
        Commands\ReturnSmsReminder::class,
        Commands\DeleteReportEntry::class,
        Commands\UpdateReportEntry::class,
        Commands\DebtorsToTenderEntries::class,
        UnbalancedTrailBalanceChecker::class,
        Commands\Telematics\OverspeedingAlert::class,
        RunEndOfDayChecks::class,
        Commands\Telematics\AutoSwitchOffVehicles::class,
        Commands\Telematics\AutoSwitchOnVehicles::class,
        Commands\PopulateNceStockCounts::class,
        Commands\Telematics\AutoSwitchOffVehiclesAtFivePm::class,
        Commands\Telematics\CopyDataToNewTable::class,
        Commands\Telematics\CreateVehicleExemptionSchedules::class,
        Commands\ArchivePurchaseOrders::class,
        Commands\BlockStockTakeUsers::class,
        Commands\BlockStockTakeUsersWeekly::class,
        Commands\StockTakeReminders::class,
        Commands\UndeliveredOrdersAlerts::class,
        Commands\Telematics\ExecuteCustomCommands::class,
        Commands\GeneralLedger\SendTrialBalanceStatus::class,
        Commands\Telematics\ExpireFuelLpos::class,
        Commands\Telematics\FuelVerificationCommmand::class,
        RunSalesManPerfomance::class,
        PettyCashRequestNotification::class,
        Commands\BlockRouteAccounts::class,
        Commands\StockTakeBlockSchedule::class,
        GetUnbalancedInvoices::class,
        Commands\CreateEodRoutineRecords::class,
        Commands\AutoCreateChiefCashierSale::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('app:auto-create-chief-cashier-sale')
            ->dailyAt('01:01')
            ->timezone('Africa/Nairobi');

        $schedule->command('app:create-eod-routine-records')
            ->dailyAt('01:00')
            ->timezone('Africa/Nairobi');

        $schedule->command('app:send-shift-auto-close-reminder')
            ->dailyAt('17:00')
            ->timezone('Africa/Nairobi');

        $schedule->command('app:auto-close-salesman-shifts')
            ->dailyAt('18:00')
            ->timezone('Africa/Nairobi');

        // Auto-close shifts at 7 PM and every hour after to catch any stragglers
        $schedule->command('shifts:auto-close')
            ->dailyAt('19:00')
            ->timezone('Africa/Nairobi');
        
        // Also run hourly between 8 PM and midnight to catch shifts older than 24 hours
        $schedule->command('shifts:auto-close')
            ->hourly()
            ->between('20:00', '23:59')
            ->timezone('Africa/Nairobi');

        $schedule->command('app:create-shifts')
            ->dailyAt('01:00')
            ->timezone('Africa/Nairobi');

        $schedule->command('app:send-stale-order-notification')
            ->everyFiveMinutes()
            ->between('8:00', '18:00');

        $notifications = Cache::remember('scheduled_notifications', Carbon::now()->endOfDay(), function () {
            return ScheduledNotification::all();
        });

        foreach ($notifications as $notification) {
            $time = Carbon::parse($notification->time)->format('H:i');
            $frequency = $notification->frequency;

            if ($time) {
                $this->scheduleWithTime($schedule, $frequency, $time, $notification->id);
            } else {
                $this->scheduleWithoutTime($schedule, $frequency, $notification->id);
            }
        }
        $schedule->command('app:create-daily-stock-count-sheet')
            ->dailyAt('00:00')
            ->timezone('Africa/Nairobi');
        $schedule->command('app:populate-nce-stock-counts')
            ->dailyAt('23:00')
            ->timezone('Africa/Nairobi');
        // $schedule->command('app:return-sms-reminder')
        //     ->twiceDaily(6, 18)
        //     ->timezone('Africa/Nairobi');

//        $schedule->call(function () {
//            UnbalancedCompletedInvoices::dispatch();
//        })->twiceDaily(12, 15)
//            ->timezone('Africa/Nairobi');

        $schedule->call(function () {
            UnbalancedCompletedInvoices::dispatch();
        })->twiceDaily(18, 19)
            ->timezone('Africa/Nairobi');

        $schedule->command('app:unbalanced-trail-balance-checker')
            ->dailyAt('00:00')
            ->timezone('Africa/Nairobi');
        $schedule->command('app:run-end-of-day-checks')
            ->dailyAt('21:00')
            ->timezone('Africa/Nairobi');
        // $schedule->command('app:overspeeding-alert')
        //     ->everyTwoMinutes();
        $schedule->command('app:auto-switch-off-vehicles')
            ->dailyAt('22:00');
        $schedule->command('app:auto-switch-on-vehicles')
            ->dailyAt('03:30');
        $schedule->command('app:auto-switch-off-vehicles-at-five-pm')
            ->dailyAt('17:00');

        $schedule->command('purchase-orders:archive')->daily();
        $schedule->command('app:create-vehicle-exemption-schedules')
            ->dailyAt('01:00');
        // $schedule->command('app:block-stock-take-users')
        //     ->dailyAt('23:00');
        // $schedule->command('app:block-stock-take-users-weekly')
        //     ->weeklyOn(1, '00:00');	 
        $schedule->command('app:stock-take-reminders')
        ->dailyAt(15)
        ->timezone('Africa/Nairobi');
        $schedule->command('app:undelivered-orders-alerts')
            ->daily('23:00');
         $schedule->command('app:execute-custom-commands')
            ->everyFiveMinutes();
        
        // send trial balance status notification
        $schedule->command('app:send-trial-balance-status')
            ->dailyAt('21:00');
        $schedule->command('app:expire-fuel-lpos')
            ->dailyAt('00:00');
        $schedule->command('app:flash-sales-disable')
            ->dailyAt('00:00');
        $schedule->command('app:fuel-verification-commmand')
            ->dailyAt('04:00')
            ->timezone('Africa/Nairobi');
        $schedule->command('app:block-route-accounts')
            ->dailyAt('00:05')
            ->timezone('Africa/Nairobi');
        $schedule->command('app:stock-take-block-schedule')
            ->dailyAt('01:00')
            ->timezone('Africa/Nairobi');

        // Send petty cash notifications
        $pettyCashNotificationsTimes = ['07:00', '12:00', '18:00'];
        // foreach($pettyCashNotificationsTimes as $time) {
        //     $schedule->command('app:petty-cash-request-notification')
        //         ->dailyAt($time);
        // }
    }

    protected function scheduleWithTime(Schedule $schedule, string $frequency, string $time, int $notificationId)
    {
        switch ($frequency) {
            case 'daily':
                $schedule->command("notifications:send $notificationId")->dailyAt($time);
                break;
            case 'weekly':
                $schedule->command("notifications:send $notificationId")->weekly()->at($time);
                break;
            case 'monthly':
                $schedule->command("notifications:send $notificationId")->monthly()->at($time);
                break;
                // Add other frequencies as needed
        }
    }

    protected function scheduleWithoutTime(Schedule $schedule, string $frequency, int $notificationId)
    {
        switch ($frequency) {
            case 'daily':
                $schedule->command("notifications:send $notificationId")->daily();
                break;
            case 'weekly':
                $schedule->command("notifications:send $notificationId")->weekly();
                break;
            case 'monthly':
                $schedule->command("notifications:send $notificationId")->monthly();
                break;
                // Add other frequencies as needed
        }
    }

    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
