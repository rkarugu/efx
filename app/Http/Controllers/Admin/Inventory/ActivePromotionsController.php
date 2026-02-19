<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\ItemPromotion;
use App\Models\PromotionGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActivePromotionsController extends Controller
{
    protected $model;
    protected $title;
    protected $pmodule;
    protected  $basePath;

    public function __construct(Request $request)
    {
        $this->model = 'active-promotions';
        $this->title = 'Active Promotions';
        $this->pmodule = 'active-promotions';

    }
    public function index()
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        $currentDate = Carbon::now()->toDateString();
        $promotionsGroups = PromotionGroup::where('active', true)
            ->whereDate('start_time', '<=', $currentDate)
            ->whereDate('end_time', '>=', $currentDate)
            ->get();

        return view('admin.inventory.item.activePromotions.index', compact('permission','pmodule','title','model','promotionsGroups'));

    }

    public function show(Request $request, $id)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        $promotions = ItemPromotion::with('promotionType','promotionGroup','inventoryItem')->where('promotion_group_id', $id)->where('status', 'active')->get();

        return view('admin.inventory.item.activePromotions.show', compact('permission','pmodule','title','model','promotions'));
    }
}
