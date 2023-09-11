<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bank\Order;
use App\Services\Bank\Analytics\CardAnalyticsService;
use App\Services\Bank\CardService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{

    public function index(Request $request) {
        if(!auth()?->user()?->can('view-analytics')){
            abort(403);
        }
        $data = [];

        $res = CardService::instance()->getCards(0, 1000);
        $data['cards'] = $res['cards'] ?? [];

        $data['order_statuses'] = [
            'all' => [
                'label' => 'All',
                'value' => null,
                'selected' => false,
            ],
            'completed' => [
                'label' => 'Completed',
                'value' => 'completed',
                'selected' => false,
            ],
            'failed' => [
                'label' => 'Failed',
                'value' => 'failed',
                'selected' => false,
            ],
        ];

        if($request->method() === "POST"){
            $cards = $request->input('cards');
            $date = $request->input('date_range');
            $group_by = $request->input('group_by');
            $order_statuses = $request->input('order_statuses');

            if(isset($data['order_statuses'][$order_statuses])){
                $data['order_statuses'][$order_statuses]['selected'] = true;
                $order_statuses = $data['order_statuses'][$order_statuses]['value'];
            }else{
                $order_statuses = null;
            }

            $args = CardAnalyticsService::instance()->buildCardsTotalsRequest([
                'card_id' => $cards,
                'date_range' => $date,
                'group_by' => $group_by,
                'order_statuses' => $order_statuses ?? null,
            ]);
            $data['selected'] = $args;

            $data['result'] = CardAnalyticsService::instance()->getCardsTotals($args);
        }else{
            $data['order_statuses']['all']['selected'] = true;
        }

        return view('admin.pages.analytics.index', $data);
    }

}
