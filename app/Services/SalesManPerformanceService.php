<?php

namespace App\Services;

use App\DeliverySchedule;
use App\Model\Order;
use App\Model\Route;
use App\Model\User;
use App\Model\WaInternalRequisition;
use App\Model\WaInternalRequisitionItem;
use App\Model\WaInventoryLocationTransfer;
use App\Model\WaRouteCustomer;
use App\Models\IncentiveSettings;
use App\SalesmanShift;
use App\SalesmanShiftCustomer;
use App\WaInventoryLocationTransferItemReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesManPerformanceService
{

    public function salesman($user_id,$date = null)
    {

         if ($date)
         {
             [$month, $year] = explode('/', $date);
             $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
             $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();
         }else
         {
             $start = now()->startOfMonth();
             $end = now()->endOfMonth();
         }


        $uniqueRouteIds = SalesmanShift::where('salesman_id', $user_id)
            ->whereIn('status', ['open', 'close'])
            ->whereBetween('created_at', [$start, $end])
            ->pluck('route_id')
            ->unique()
            ->values();

        $achieved_tonnage_subquery = DB::table('wa_internal_requisition_items')
            ->select(
                DB::raw('SUM(wa_internal_requisition_items.total_cost_with_vat) as sub_query_route_sales'),
                DB::raw('SUM((wa_internal_requisition_items.quantity * wa_inventory_items.net_weight)/1000) as sub_query_achieved_tonnage'),
                DB::raw('COUNT(DISTINCT CASE WHEN wa_inventory_items.pack_size_id IN (6, 9, 17, 4, 10, 1) THEN CONCAT(DATE(wa_internal_requisition_items.created_at), "-", wa_internal_requisition_items.wa_inventory_item_id) ELSE NULL END) as dzns_tonnage'),
                DB::raw('COUNT(DISTINCT CASE WHEN wa_inventory_items.pack_size_id = 3 THEN CONCAT(DATE(wa_internal_requisition_items.created_at), "-", wa_internal_requisition_items.wa_inventory_item_id) ELSE NULL END) as ctns_tonnage'),
                DB::raw('COUNT(DISTINCT CASE WHEN wa_inventory_items.pack_size_id NOT IN (6, 9, 17, 4, 10, 1, 3) THEN CONCAT(DATE(wa_internal_requisition_items.created_at), "-", wa_internal_requisition_items.wa_inventory_item_id) ELSE NULL END) as bulk_tonnage'),
                'wa_internal_requisitions.route_id',

            )
            ->leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->leftJoin('wa_inventory_items', 'wa_inventory_items.id', 'wa_internal_requisition_items.wa_inventory_item_id')
            ->where('wa_internal_requisitions.requisition_no', 'like', 'INV%')
            ->whereBetween('wa_internal_requisitions.created_at', [$start, $end])
            ->groupBy('wa_internal_requisitions.route_id');

        $data = DB::table('routes')
            ->select(
                'routes.id as route_id',
                'routes.route_name as route',
                'users.id as salesman_id',
                 'users.name as salesman',
                'routes.tonnage_target',
                'routes.ctn_target',
                'routes.dzn_target',
                'routes.group',
                'achieved_tonnage_subquery.sub_query_route_sales AS sales',
                'achieved_tonnage_subquery.sub_query_achieved_tonnage AS achieved_tonnage',
                'achieved_tonnage_subquery.dzns_tonnage AS dzns_tonnage',
                'achieved_tonnage_subquery.ctns_tonnage AS ctns_tonnage',
                'achieved_tonnage_subquery.bulk_tonnage AS bulk_tonnage',
                DB::raw("(SELECT route_users.name
                        FROM salesman_shifts
                        LEFT JOIN users route_users ON salesman_shifts.salesman_id = route_users.id
                        WHERE salesman_shifts.route_id = routes.id
                            AND salesman_shifts.status IN ('open', 'close', 'not_started')
                            AND (salesman_shifts.created_at BETWEEN '$start' AND '$end')
                        ORDER BY salesman_shifts.created_at DESC
                        LIMIT 1
                    ) AS salesman"),

                DB::raw("(SELECT COUNT(DISTINCT DATE(salesman_shifts.created_at)) 
                        FROM salesman_shifts WHERE salesman_shifts.route_id = routes.id AND salesman_shifts.status IN ('open', 'close', 'not_started')
                        AND (salesman_shifts.created_at BETWEEN '$start' AND '$end')
                    ) AS actual_frequency"),
                DB::raw("(SELECT COUNT(DISTINCT DATE(salesman_shifts.created_at)) 
                        FROM salesman_shifts WHERE salesman_shifts.route_id = routes.id AND salesman_shifts.status IN ('open', 'close')
                        AND (salesman_shifts.created_at BETWEEN '$start' AND '$end')
                    ) AS days_with_actual_shifts"),

                DB::raw("(SELECT COUNT(wa_route_customers.id) 
                        FROM wa_route_customers 
                        WHERE wa_route_customers.route_id = routes.id 
                        AND wa_route_customers.deleted_at IS NULL
                    ) AS route_customers_count"),
                DB::raw("((SELECT COUNT(DISTINCT DATE(salesman_shifts.created_at)) 
                        FROM salesman_shifts WHERE salesman_shifts.route_id = routes.id AND salesman_shifts.status IN ('open', 'close', 'not_started')
                        AND (salesman_shifts.created_at BETWEEN '$start' AND '$end')
                    ) *
                    (SELECT COUNT(wa_route_customers.id)
                    FROM wa_route_customers 
                    WHERE wa_route_customers.route_id = routes.id
                    AND wa_route_customers.deleted_at IS NULL
                    )
                    ) AS total_customers"),
                DB::raw("(SELECT COUNT(DISTINCT CONCAT(salesman_shift_customers.route_customer_id, '-', DATE(salesman_shift_customers.created_at)))
                        FROM salesman_shift_customers
                        LEFT JOIN salesman_shifts ON salesman_shifts.id = salesman_shift_customers.salesman_shift_id
                        WHERE salesman_shift_customers.visited = '1'
                        AND salesman_shifts.route_id = routes.id
                        AND (salesman_shifts.created_at BETWEEN '$start' AND '$end')
                    ) AS met_customers"),
                DB::raw("(SELECT COUNT(salesman_shifts.id)
                        FROM salesman_shifts 
                        WHERE salesman_shifts.shift_type = 'onsite'
                        AND salesman_shifts.route_id = routes.id
                        AND salesman_shifts.route_id = routes.id AND salesman_shifts.status IN ('open', 'close')
                        AND (salesman_shifts.created_at BETWEEN '$start' AND '$end')
                    ) AS full_onsite"),
                DB::raw("(SELECT COUNT(salesman_shifts.id)
                        FROM salesman_shifts 
                        WHERE salesman_shifts.route_id = routes.id
                        AND salesman_shifts.route_id = routes.id AND salesman_shifts.status IN ('open', 'close')
                        AND (salesman_shifts.created_at BETWEEN '$start' AND '$end')
                        AND (TIMESTAMPDIFF(HOUR, salesman_shifts.start_time, salesman_shifts.closed_time) > routes.estimated_shift_time )
                    ) AS shifts_closed_past_time"),
                DB::raw("(SELECT COUNT(salesman_shifts.id)
                        FROM salesman_shifts 
                        WHERE salesman_shifts.route_id = routes.id
                        AND (salesman_shifts.created_at BETWEEN '$start' AND '$end')
                        AND TIME(salesman_shifts.start_time) <= '06:50:00'
                    ) AS opened_ontime"),
                DB::raw("(SELECT SUM(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price)
                        FROM wa_inventory_location_transfer_item_returns 
                        LEFT JOIN wa_inventory_location_transfer_items ON wa_inventory_location_transfer_items.id = wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id
                        LEFT JOIN wa_inventory_location_transfers ON wa_inventory_location_transfers.id = wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id
                        WHERE wa_inventory_location_transfers.route_id = routes.id 
                        AND wa_inventory_location_transfer_item_returns.status = 'received'
                        AND wa_inventory_location_transfer_item_returns.return_status = '1'
                        AND (wa_inventory_location_transfer_item_returns.updated_at BETWEEN '$start' AND '$end')
                    ) AS returns"),

            )->leftJoin('route_user', 'route_user.route_id', 'routes.id')
            ->leftJoin('users', 'users.id', 'route_user.user_id')
            ->leftJoinSub($achieved_tonnage_subquery, 'achieved_tonnage_subquery', 'achieved_tonnage_subquery.route_id', 'routes.id')
            ->whereIn('routes.id',$uniqueRouteIds)
            ->where('routes.is_physical_route', 1)
            ->groupBy('routes.id');

        $data = $data->get();


        $items = [];

        $setIncentives = IncentiveSettings::select('slug','target_reward','group')->get();

        $combinedIncentives = [];
        foreach ($setIncentives->toArray() as $incentive) {
            $combinedIncentives[$incentive['slug']] = json_decode($incentive['target_reward']);
        }
        foreach ($data as $datum)
        {
            $tonnage_target = $datum->actual_frequency * $datum ->tonnage_target;
            $total_tonnage = $datum->achieved_tonnage ;

            $tonnage_percentage = $total_tonnage > 0 ? number_format(($total_tonnage/ $tonnage_target) * 100, 2) : 0;
            $cartons_percent =  $datum->ctns_tonnage > 0 ? number_format($datum->ctns_tonnage / $datum ->achieved_tonnage * 100): 0;
            $dozen_percent = $datum ->dzns_tonnage > 0 ?  number_format($datum ->dzns_tonnage / $datum ->achieved_tonnage * 100): 0;
            $bulk_percent = $datum->bulk_tonnage > 0 ? number_format($datum->bulk_tonnage / $datum ->achieved_tonnage * 100): 0;

            $onsitePercentage = $datum->full_onsite > 0 ? ( $datum->full_onsite /  $datum->actual_frequency) * 100 : 0;
            $unmet_percentage = $datum->met_customers > 0 ?  ($datum->met_customers/$datum->total_customers)*100:0;
            $early_shift_percentage = $datum->opened_ontime >0 ? ($datum->opened_ontime/$datum->actual_frequency)*100: 0;
            $shifts_closed_on_time_percentage = $datum->shifts_closed_past_time>0? 100 - (($datum->shifts_closed_past_time / $datum ->days_with_actual_shifts)*100):0;

            $expectedCtns = ($datum->ctn_target * $datum->actual_frequency);
            $expectedDzns = ($datum->dzn_target * $datum->actual_frequency);
            $ctnPercent = $expectedCtns > 0 ? ($datum->ctns_tonnage / $expectedCtns) * 100 : 0;
            $dznPercent = $expectedCtns > 0 ? ($datum->dzns_tonnage / $expectedDzns) * 100 : 0;

            $total_returns  = $datum->returns;

            $item = [
                "route_name" => "$datum->route",
                "salesman" => "$datum->salesman",
                "salesman_id" => "$datum->salesman_id",
                'incentives'=> [
                    'tonnage'=>[
                        'frequency'=>$datum->actual_frequency,
                        "title" => "Tonnage",
                        "total" => $datum->actual_frequency * $datum ->tonnage_target,
                        "group"=>  $setIncentives->firstWhere('slug', 'tonnage')->group,
                        "current" => [
                            "value" => $total_tonnage,
                            "percentage" => $total_tonnage > 0 ? number_format(($total_tonnage/ $tonnage_target) * 100, 2) : '0.00'
                        ],
                        'bands' => array_map(function($incentive) use ($tonnage_percentage) {
                            return [
                                'title' => "{$incentive->target}",
                                'target' => $incentive->target,
                                'reward' => $incentive->reward,
                                'achieved' => $tonnage_percentage >= $incentive->target
                            ];
                        }, $combinedIncentives['tonnage']),
                        'earned'=> $this->calculateIncentive($tonnage_percentage, $combinedIncentives['tonnage'])
                    ],
                    "cartons" => [
                        "title" => "Cartons",
                        "total" => $datum ->ctns_tonnage,
                        "group"=>  null,
                        "current" => [
                            "value" => $datum ->ctns_tonnage,
                            "percentage" => $cartons_percent                    ],
                        'bands' => array_map(function($incentive) use ($ctnPercent) {
                            return [
                                'title' => "{$incentive->target}%",
                                'target' => $incentive->target,
                                'reward' => $incentive->reward,
                                'achieved' => $ctnPercent >= $incentive->target
                            ];
                        }, $combinedIncentives['cartons']),
                        'earned'=> $this->calculateIncentive($ctnPercent, $combinedIncentives['cartons']),
                    ],
                    "dozens" => [
                        "title" => "Dozens",
                        "total" => $datum ->dzns_tonnage,
                        "group"=>  null,
                        "current" => [
                            "value" => $datum ->ctns_tonnage,
                            "percentage" => $dozen_percent                    ],
                        'bands' => array_map(function($incentive) use ($dznPercent) {
                            return [
                                'title' => "{$incentive->target}%",
                                'target' => $incentive->target,
                                'reward' => $incentive->reward,
                                'achieved' => $dznPercent >= $incentive->target
                            ];
                        }, $combinedIncentives['dozens']),
                        'earned'=> $this->calculateIncentive($dznPercent, $combinedIncentives['dozens']),
                    ],
//                    "bulk" => [
//                        "title" => "Bulk",
//                        "total" => $datum ->bulk_tonnage,
//                        "group"=> null,
//                        "current" => [
//                            "value" => $datum ->bulk_tonnage,
//                            "percentage" => $bulk_percent                    ],
//                        'bands' => array_map(function($incentive) use ($bulk_percent) {
//                            return [
//                                'title' => "{$incentive->target}%",
//                                'target' => $incentive->target,
//                                'reward' => $incentive->reward,
//                                'achieved' => $bulk_percent >= $incentive->target
//                            ];
//                        }, $combinedIncentives['bulk']),
//                        'earned'=> $this->calculateIncentive($bulk_percent, $combinedIncentives['bulk']),
//                    ],
                    'met_customers' => [
                        'title' => 'Met Customers',
                        'total' => $datum->total_customers,
                        "group"=>  $setIncentives->firstWhere('slug', 'met_customers')->group,
                        'current' => [
                            'value' => $datum->met_customers,
                            'percentage' => $unmet_percentage,
                        ],

                        'bands' => array_map(function($incentive) use ($unmet_percentage) {
                            return [
                                'title' => "Target {$incentive->target}%",
                                'target' => $incentive->target,
                                'reward' => $incentive->reward,
                                'achieved' => $unmet_percentage >= $incentive->target
                            ];
                        }, $combinedIncentives['met_customers']),
                        'earned'=> $this->calculateIncentive($unmet_percentage, $combinedIncentives['met_customers'])
                    ],
                    'onsite' => [
                        "title" => "Onsite Shifts",
                        "current_total_shifts" => $datum->actual_frequency,
                        "total" => $datum->actual_frequency,
                        "group"=> $setIncentives->firstWhere('slug', 'onsite')->group,
                        "current" => [
                            "value" => $datum->full_onsite,
                            "percentage" => $onsitePercentage,
                        ],
                        'bands' => array_map(function($incentive) use ($onsitePercentage) {
                            return [
                                'title' => "Target {$incentive->target}%",
                                'target' => $incentive->target,
                                'reward' => $incentive->reward,
                                'achieved' => $onsitePercentage >= $incentive->target
                            ];
                        }, $combinedIncentives['onsite']),
                        'earned'=> $this->calculateIncentive($onsitePercentage, $combinedIncentives['onsite'])

                    ],
                    'early_shifts'=> [
                        "title" => "Shifts by 6:30AM",
                        "total" => $datum->actual_frequency,
                        "group"=>  $setIncentives->firstWhere('slug', 'early_shifts')->group,
                        "current" => [
                            "value" => $datum->opened_ontime,
                            "percentage" => $early_shift_percentage,
                        ],
                        'bands' => array_map(function($incentive) use ($early_shift_percentage) {
                            return [
                                'title' => "Target {$incentive->target}%",
                                'target' => $incentive->target,
                                'reward' => $incentive->reward,
                                'achieved' => $early_shift_percentage >= $incentive->target
                            ];
                        }, $combinedIncentives['early_shifts']),
                        'earned'=> $this->calculateIncentive($early_shift_percentage, $combinedIncentives['early_shifts'])
                    ],
                    'returns' => [
                        "title" => "Returns",
                        "total" => 0,
                        "current" => [
                            "value" => $datum->returns,
                            "percentage" => $datum->returns,
                        ],
                        'bands' => array_map(function($incentive) use ($total_returns) {
                            return [
                                'title' => "Target {$incentive->target}%",
                                'target' => $incentive->target,
                                'reward' => $incentive->reward,
                                'achieved' => $total_returns >= $incentive->target
                            ];
                        }, $combinedIncentives['early_shifts']),
                        'earned'=> $this->calculateReturnIncentive($total_returns, $combinedIncentives['returns']),
                        "group"=> $setIncentives->firstWhere('slug', 'returns')->group,
                    ],
                    "time_management" => [
                        "title" => "Time Management",
                        "total" => $datum->shifts_closed_past_time,
                        "group"=>  $setIncentives->firstWhere('slug', 'time_management')->group,
                        "current" => [
                            "value" => $datum->shifts_closed_past_time,
                            "percentage" => $shifts_closed_on_time_percentage
                        ],
                        'bands' => array_map(function($incentive) use ($shifts_closed_on_time_percentage) {
                            return [
                                'title' => "Target {$incentive->target}%",
                                'target' => $incentive->target,
                                'reward' => $incentive->reward,
                                'achieved' => $shifts_closed_on_time_percentage >= $incentive->target
                            ];
                        }, $combinedIncentives['time_management']),
                        'earned'=> $this->calculateIncentive($shifts_closed_on_time_percentage, $combinedIncentives['time_management']),
                    ],
//                "pay_on_delivery" => [
//                    "title" => "Paid On Delivery",
//                    "total" => 300,
//                    "current" => [
//                        "value" => 30,
//                        "percentage" => 10
//                    ],
//                    'bands' => array_map(function($incentive) {
//                        return [
//                            'title' => "Target {$incentive->target}%",
//                            'target' => $incentive->target,
//                            'reward' => $incentive->reward,
//                            'achieved' => 30 >= $incentive->target
//                        ];
//                    }, $combinedIncentives['pay_on_delivery']),
//                    'earned'=> $this->calculateIncentive(30, $combinedIncentives['pay_on_delivery']),
//                ]
                ],

            ];

            $item['incentive_total'] = $this->calculateTotalIncentive($item['incentives']);
            $items[]= $item;
        }
        return response()->json($items);
    }

    public function calculateIncentive($current, $bands)
    {
        $earned = 0;
        usort($bands, function($a, $b) {
            return $b->target <=> $a->target;
        });
        foreach ($bands as $incentive) {
            $target = $incentive->target;
            $reward = $incentive->reward;

            if ($current >= $target) {
                $earned = $reward;
                break;
            }
        }
        return $earned;
    }
    public function calculateReturnIncentive($current, $bands)
    {
        $earned = 0;
        usort($bands, function($a, $b) {
            return $a->target <=> $b->target;
        });
        foreach ($bands as $incentive) {
            $target = $incentive->target;
            $reward = $incentive->reward;

            if ($current <= $target) {
                $earned = $reward;
                break;
            }
        }
        return $earned;
    }
    public function calculateTotalIncentive($incentives)
    {
        $totalEarned = 0;
        $groupedIncentives = [];

        foreach ($incentives as $key => $incentive) {
            if (isset($incentive['group']) && $incentive['group'] !== null) {

                $groupedIncentives[$incentive['group']][] = $incentive;
//                $groupedIncentives[] = $incentive;
            } else {
                // Add non-grouped incentives directly to total
                $totalEarned += $incentive['earned'] ?? 0;
            }
        }


        // Handle grouped incentives
        foreach ($groupedIncentives as $group) {
            $allAchieved = true;
            $totalReward = 0;

            // Check if all incentives in the group are achieved
            foreach ($group as $groupIncentive) {
                if ($groupIncentive['earned'] == 0) {
                    $allAchieved = false;
                    break;
                }
                $totalReward += $groupIncentive['reward'];
            }

            // If all are achieved, add the average reward to total
            if ($allAchieved) {
                $averageReward = $totalReward / count($group);
                $totalEarned += $averageReward;
            }
        }

        return $totalEarned;
    }

    public function calculateFuelIncentive($fueledBelowExpectationPercentage, $fueledWithinExpectationPercentage, $bands)
    {
        $earned = 0;
        $found = false;

        foreach ($bands as $band) {
            if ($band->operation === 'less_than' && $fueledBelowExpectationPercentage >= $band->target) {
                $earned = $band->reward;
                $found = true;
                break;
            }
        }

        if (!$found) {
            foreach ($bands as $band) {
                if ($band->operation === 'equal' && $fueledWithinExpectationPercentage == $band->target) {
                    $earned = $band->reward;
                    break;
                }
            }
        }

        return $earned;
    }

    public function driver($user_id, $date = null)
    {

        if ($date)
        {
            [$month, $year] = explode('/', $date);
            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        }else
        {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
        }


        $setIncentives = IncentiveSettings::select('slug','target_reward','group')->get();

        $combinedIncentives = [];
        foreach ($setIncentives->toArray() as $incentive) {
            $combinedIncentives[$incentive['slug']] = json_decode($incentive['target_reward']);
        }

        $data = DB::table('users')
            ->select(
                'users.name as driver',
                DB::raw("(SELECT COUNT(delivery_schedules.id)
                        FROM delivery_schedules
                        WHERE delivery_schedules.driver_id = users.id
                        AND (DATE(delivery_schedules.created_at)  BETWEEN '$start' AND '$end')
                    ) AS driver_shifts"),
                DB::raw("(SELECT COUNT(delivery_schedules.id)
                        FROM delivery_schedules
                        WHERE delivery_schedules.driver_id = users.id
                        AND (DATE(delivery_schedules.created_at)  BETWEEN '$start' AND '$end')
                        AND (TIME(delivery_schedules.actual_delivery_date) <= '06:00:00')
                    ) AS shifts_started_on_time"),
                DB::raw("(SELECT COUNT(delivery_schedules.id)
                        FROM delivery_schedules
                        WHERE delivery_schedules.driver_id = users.id
                        AND (DATE(delivery_schedules.created_at)  BETWEEN '$start' AND '$end')
                        AND (TIME(delivery_schedules.finish_time) <= '17:00:00')
                        AND DATE(delivery_schedules.actual_delivery_date) = DATE(delivery_schedules.finish_time)
                    ) AS shifts_ended_on_time"),
                DB::raw("(SELECT COUNT(wa_internal_requisitions.id)
                        FROM delivery_schedules
                        LEFT JOIN salesman_shifts ON salesman_shifts.id = delivery_schedules.shift_id
                        LEFT JOIN wa_internal_requisitions ON salesman_shifts.id = wa_internal_requisitions.wa_shift_id
                        WHERE delivery_schedules.driver_id = users.id
                        AND (DATE(delivery_schedules.created_at)  BETWEEN '$start' AND '$end')
                    ) AS expected_deliveries"),
                DB::raw("(SELECT COUNT(wa_internal_requisitions.id)
                        FROM delivery_schedules
                        LEFT JOIN salesman_shifts ON salesman_shifts.id = delivery_schedules.shift_id
                        LEFT JOIN wa_internal_requisitions ON salesman_shifts.id = wa_internal_requisitions.wa_shift_id
                        WHERE delivery_schedules.driver_id = users.id
                        AND (DATE(delivery_schedules.created_at)  BETWEEN '$start' AND '$end')
                        AND wa_internal_requisitions.status  = 'DELIVERED'
                    ) AS actual_deliveries"),
                DB::raw("(SELECT COUNT(fuel_entries.id)
                        FROM delivery_schedules
                        LEFT JOIN fuel_entries  ON fuel_entries.shift_id = delivery_schedules.id
                        WHERE delivery_schedules.driver_id = users.id
                        AND fuel_entries.shift_type = 'Route Deliveries'
                        AND (DATE(delivery_schedules.created_at)  BETWEEN '$start' AND '$end')
                        AND DATE(delivery_schedules.actual_delivery_date) = DATE(delivery_schedules.finish_time)
                    ) AS total_fuel_entries"),
                DB::raw("(SELECT COUNT(fuel_entries.id)
                        FROM delivery_schedules
                        LEFT JOIN fuel_entries ON fuel_entries.shift_id = delivery_schedules.id
                        WHERE delivery_schedules.driver_id = users.id
                        AND fuel_entries.shift_type = 'Route Deliveries'
                        AND (DATE(delivery_schedules.created_at)  BETWEEN '$start' AND '$end')
                        AND DATE(delivery_schedules.actual_delivery_date) = DATE(delivery_schedules.finish_time)
                        AND fuel_entries.actual_fuel_quantity < fuel_entries.shift_fuel_estimate
                    ) AS fueled_less_than_expected"),
                DB::raw("(SELECT COUNT(fuel_entries.id)
                        FROM delivery_schedules
                        LEFT JOIN fuel_entries  ON fuel_entries.shift_id = delivery_schedules.id
                        WHERE delivery_schedules.driver_id = users.id
                        AND fuel_entries.shift_type = 'Route Deliveries'
                        AND (DATE(delivery_schedules.created_at)  BETWEEN '$start' AND '$end')
                        AND DATE(delivery_schedules.actual_delivery_date) = DATE(delivery_schedules.finish_time)
                        AND fuel_entries.actual_fuel_quantity <= fuel_entries.shift_fuel_estimate
                    ) AS fueled_within_expected"),
                DB::raw("(SELECT COUNT(salesman_shift_store_dispatches.id)
                        FROM delivery_schedules
                        LEFT JOIN salesman_shifts ON salesman_shifts.id = delivery_schedules.shift_id
                        LEFT JOIN salesman_shift_store_dispatches ON salesman_shifts.id = salesman_shift_store_dispatches.shift_id
                        WHERE delivery_schedules.driver_id = users.id
                        AND (DATE(delivery_schedules.created_at) BETWEEN '$start' AND '$end')
                        AND DATE(salesman_shifts.created_at) != DATE(salesman_shift_store_dispatches.dispatch_time)
                    ) AS shifts_dispatched_next_day"),
            )
            ->where('users.id', $user_id)->first();

        $system_usage =  $data->expected_deliveries > 0 ? number_format(($data->actual_deliveries / $data->expected_deliveries) * 100, 2) : 0;
        $back_on_time =  $data->driver_shifts > 0 ? number_format(($data->shifts_ended_on_time / $data->driver_shifts) * 100, 2) : 0;
        $start_on_time = $data->driver_shifts > 0 ? number_format(($data->shifts_started_on_time / $data->driver_shifts) * 100, 2) : 0;

        $fueledBelowExpectationPercentage = $data->total_fuel_entries > 0 ? ($data->fueled_less_than_expected / $data->total_fuel_entries) * 100 : 0;
        $fueledWithinExpectationPercentage = $data->total_fuel_entries > 0 ? ($data->fueled_within_expected / $data->total_fuel_entries) * 100 : 0;

        $array = [
            'incentives'=> [
                'shift_by_6am' => [
                    'title' => 'Shifts by 6AM',
                    'total' => $data->driver_shifts,
                    'current' => [
                        'value' => $data->shifts_ended_on_time,
                        'percentage' => $start_on_time,
                    ],

                    'bands' => array_map(function($incentive) use ($start_on_time) {
                        return [
                            'title' => "{$incentive->target}%",
                            'target' => $incentive->target,
                            'reward' => $incentive->reward,
                            'achieved' =>  $start_on_time >= $incentive->target
                        ];
                    }, $combinedIncentives['shift_by_6am']),
                    'earned'=> $this->calculateIncentive( $start_on_time, $combinedIncentives['shift_by_6am']),
                    "group"=> $setIncentives->firstWhere('slug', 'shift_by_6am')->group,
                ],
                'load_prev_day' => [
                    'title' => 'Load Prev Day',
                    'total' => 12,
                    'current' => [
                        'value' => 10,
                        'percentage' => 30.5
                    ],
                    'incentive_amount' =>  $combinedIncentives['load_prev_day'][0]->reward,
                    'within_target' => false,
                    'earned'=>0
                ],
                'back_on_time' => [
                    'title' => 'Back On Time',
                    'total' => $data->driver_shifts,
                    'current' => [
                        'value' => $data->shifts_ended_on_time,
                        'percentage' => $back_on_time
                    ],
                    'bands' => array_map(function($incentive) use ($back_on_time) {
                        return [
                            'title' => "{$incentive->target}%",
                            'target' => $incentive->target,
                            'reward' => $incentive->reward,
                            'achieved' =>  $back_on_time >= $incentive->target
                        ];
                    }, $combinedIncentives['back_on_time']),
                    'earned'=> $this->calculateIncentive( $back_on_time, $combinedIncentives['back_on_time']),
                    "group"=> $setIncentives->firstWhere('slug', 'back_on_time')->group,

                ],
                'system_usage' => [
                    'title' => 'System Usage',
                    'total' => $data ->expected_deliveries,
                    'current' => [
                        'value' => $data->actual_deliveries,
                        'percentage' => $system_usage,
                    ],
                    'bands' => array_map(function($incentive) use ($system_usage) {
                        return [
                            'title' => "{$incentive->target}%",
                            'target' => $incentive->target,
                            'reward' => $incentive->reward,
                            'achieved' =>  $system_usage >= $incentive->target
                        ];
                    }, $combinedIncentives['system_usage']),
                    'earned'=> $this->calculateIncentive( $system_usage, $combinedIncentives['system_usage']),
                    "group"=> $setIncentives->firstWhere('slug', 'system_usage')->group,
                ],
                'fuel' => [
                    'title' => 'Fuel',
                    'total' => $data->total_fuel_entries,
                    'current' => [
                        'value' => $data->total_fuel_entries,
                        'percentage' =>$data->driver_shifts > 0 ? number_format(($data->shifts_ended_on_time / $data->driver_shifts) * 100, 2) : 0,
                    ],
                    'bands' => array_map(function($incentive) use ($data) {
                        return [
                            'title' => "{$incentive->target}%",
                            'target' => $incentive->target,
                            'reward' => $incentive->reward,
                            'achieved' =>  $data->total_fuel_entries >= $incentive->target
                        ];
                    }, $combinedIncentives['fuel']),
                    'earned'=>  $this->calculateFuelIncentive($fueledBelowExpectationPercentage, $fueledWithinExpectationPercentage, $combinedIncentives['fuel']),
                    "group"=> $setIncentives->firstWhere('slug', 'fuel')->group,
                ]
            ]
        ];


        $array['incentive_total'] = $this->calculateTotalIncentive($array['incentives']);



        $percentage_range = $combinedIncentives['turn_boy_incentive'][0]->reward; // Set your percentage range
        $array['turn_boy_incentive'] = [
            'value' => ceil(($array['incentive_total'] / 100) * $percentage_range) ,
            'percentage' => $percentage_range,
        ];

        return response()->json($array);

    }


}