<?php

namespace App\Services\Bank\Analytics;

use Illuminate\Support\Facades\Http;

class CardAnalyticsService
{

    use BaseAnalyticsService;

    public function getCardsTotals(array $args): array|null
    {
        try{
            $res = Http::post($this->analyticsUrl("/card"), $args);
            if($res->status() !== 200){
                return null;
            }
            $data = json_decode($res->body(), true);
            return $data['totals'] ?? null;
        }catch (\Throwable $ex){
            return null;
        }
    }

    public function buildCardsTotalsRequest(array $data): array
    {
        $order_statuses = $data['order_statuses'] ?? [];
        $args = [
            'card_id' => array_map('intval', $data['card_id'] ?? []),
            'group_by' => $data['group_by'],
            'order_statuses' => is_string($order_statuses) ? [$order_statuses] : $order_statuses,
        ];
        if(isset($data['date_range'])){
            $date = explode('-', $data['date_range']);
            if(count($date) === 2){
                $from = date_create_from_format('d.m.Y H:i:sP', trim($date[0]));
                if($from){
                    $args['date_from'] = $from->getTimestamp();
                }

                $to = date_create_from_format('d.m.Y H:i:sP', trim($date[1]));
                if($to){
                    $args['date_to'] = $to->getTimestamp();
                }
            }
        }

        return $args;
    }

}
