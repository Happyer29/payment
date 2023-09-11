<?php

namespace App\Services\Bank;

use App\Http\Resources\Bank\Order;
use App\Http\Resources\Bank\PaymentLink;
use App\Models\User;
use App\Services\BaseFilterService;
use Illuminate\Support\Facades\Http;

class OrderService extends BaseFilterService
{
    use BaseCrudService;

    private string $model = Order::class;

    /**
     * @param int $page
     * @param int $size
     * @param string $ordering
     * @param int $owner_id
     * @param int $shop_id
     * @return array|null
     */
    public function getOrders(int $page, int $size, string $ordering="ASC", int $owner_id = 0, int $shop_id = 0): ?array
    {
        $page = max($page, 1);
        $size = max($size, 1);

        try{
            $url = "/order/list?page=$page&size=$size&order=$ordering&owner_id=$owner_id&shop_id=$shop_id";
            $res = Http::get($this->crudUrl($url));
            if($res->status() !== 200){
                return null;
            }
            $data = json_decode($res->body(), true);
        }catch (\Throwable $ex){
            return null;
        }

        $result = [
            'total' => intval($data['total'] ?? 0),
            'orders' => [],
        ];
        foreach($data['orders'] ?? [] as $order){
            $result['orders'][] = Order::make($order);
        }
        return $result;
    }

    public function getOrder(int $id): ?Order
    {
        $res = Http::get($this->crudUrl('/order/read?id='.$id));
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $order = $data['order'] ?? null;
        if(!$order){
            return null;
        }
        return Order::make($order);
    }

    public function createOrder(array $order): ?Order
    {
        $res = Http::post($this->crudUrl('/order/create'), $order);
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $order = $data['order'] ?? null;
        if(!$order){
            return null;
        }
        return Order::make($order);
    }

    public function updateOrder(array $order): ?Order
    {
        $res = Http::post($this->crudUrl('/order/update'), $order);
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $order = $data['order'] ?? null;
        if(!$order){
            return null;
        }
        return Order::make($order);
    }

    public function getLinks(int $page, int $size, string $ordering="ASC", int $owner_id = 0, int $shop_id = 0): ?array
    {
        $page = max($page, 1);
        $size = max($size, 1);

        try{
            $res = Http::get($this->crudUrl("/payment_link/list?page=$page&size=$size&order=$ordering&owner_id=$owner_id&shop_id=$shop_id"));
            if($res->status() !== 200){
                return null;
            }
            $data = json_decode($res->body(), true);
        }catch (\Throwable $ex){
            return null;
        }

        $result = [
            'total' => intval($data['total'] ?? 0),
            'payment_links' => [],
        ];
        foreach($data['payment_links'] ?? [] as $order){
            $result['payment_links'][] = PaymentLink::make($order);
        }
        return $result;
    }

    public function getLink(int $id): PaymentLink|null
    {
        $res = Http::get($this->crudUrl('/payment_link/read?id='.$id));
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $order = $data['payment_link'] ?? null;
        if(!$order){
            return null;
        }
        return PaymentLink::make($order);
    }

    public function findOrders(int $page, int $size, array $filters): array|null
    {
        $page = max($page, 1);
        $size = max($size, 1);

        try{
            $url = "/order/find?page=$page&size=$size";
            $dto = $this->buildFindDto($filters);
            //dd($dto);
            $res = Http::post($this->crudUrl($url), $dto);
            if($res->status() !== 200){
                return null;
            }
            $data = json_decode($res->body(), true);
        }catch (\Throwable $ex){
            return null;
        }

        $result = [
            'total' => intval($data['total'] ?? 0),
            'orders' => [],
        ];
        foreach($data['orders'] ?? [] as $order){
            $result['orders'][] = Order::make($order);
        }
        return $result;
    }

    protected function buildFindDto(array $filters): array|null
    {
        $dto = [];

        $user = auth()?->user();
        if(!$user->id){
            return null;
        }

        // owner_id
        $viewAny = $user?->can('viewAny', Order::class);
        if($viewAny){
            $emails = $filters['owner_email']['value'] ?? null;
            if(is_string($emails) and !empty($emails)){
                $emails = preg_split('/\s*,\s*/', trim($emails));

                foreach ($emails as $owner_email){
                    if($owner_email and is_string($owner_email)){
                        if($owner = User::where('email', $owner_email)->take(1)->first()){
                            $dto['owner_id'][] = $owner?->id;
                        }else{
                            $dto['owner_id'] = [0];
                        }
                    }
                }
            }
        }else{
            $dto['owner_id'] = [$user->id];
        }

        // status
        if(isset($filters['status'])){
            $value = $filters['status']['value'] ?? null;
            $options = $filters['status']['options'];
            $default = $filters['status']['default'] ?? null;

            if(isset($options[$value]) and $value !== $default){
                $dto['status'] = explode(',', $value);
            }else{
                $dto['status'] = [];
            }
        }

        // search
        if(isset($filters['search'])){
            $value = $filters['search']['value'] ?? null;
            if(is_string($value) and strlen(trim($value)) > 0){
                $dto['search'] = trim($value);
            }
        }

        // shop_id
        if(isset($filters['shop_id'])){
            $shop_ids = $filters['shop_id']['value'] ?? null;
            if(is_string($shop_ids)){
                $shop_ids = preg_split('/\s*,\s*/', $shop_ids);
                $shop_ids = array_map('intval', $shop_ids);
                foreach($shop_ids as $id){
//                    $shop = ShopService::instance()->getShop($id);
//                    if($shop and $user?->can('view', $shop)){
                    if($id > 0){
                        $dto['shop_id'][] = $id;
                    }
                }
            }
        }

        // order_id
        if(isset($filters['order_id'])){
            $order_ids = $filters['order_id']['value'] ?? null;
            if(is_string($order_ids)){
                $order_ids = preg_split('/\s*,\s*/', $order_ids);
                $order_ids = array_map('intval', $order_ids);
                foreach($order_ids as $id){
                    if($id > 0){
                        $dto['id'][] = $id;
                    }
                }
            }
        }

        // sort
        if(isset($filters['sort']['value'])){
            $sort = $filters['sort']['value'];
            $sorting = explode(',', $sort);
            if(isset($filters['sort']['options'][$sort]) and count($sorting) === 2){
                $dto['sort'] = [
                    'field' => $sorting[0],
                    'direction' => strtoupper($sorting[1]),
                ];
            }
        }

        return $dto;
    }

    public function getDefaultFilters(): array
    {
        return [
            'search' => [
                'label' => 'Search',
                'type' => 'text',
            ],
            'order_id' => [
                'label' => 'Order id',
                'type' => 'text',
            ],
            'owner_email' => [
                'label' => 'Owner email',
                'type' => 'text',
                'can' => ['viewAny', Order::class],
            ],
            'shop_id' => [
                'label' => 'Shop id',
                'type' => 'text',
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    'all' => 'Show all',
                    'completed,failed' => 'Completed and Failed',
                    'new' => 'New',
                    'pending' => 'Pending',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ],
                'default' => 'all',
            ],
            'sort' => [
                'label' => 'Sort',
                'type' => 'select',
                'options' => [
                    'default' => 'Default',
                    'id,asc' => 'ID',
                    'id,desc' => 'ID reverse',
//                    'shop_id,asc' => 'Shop ID',
//                    'shop_id,desc' => 'Shop ID reverse',
                    'number,asc' => 'Number A-Z',
                    'number,desc' => 'Number Z-A',
                    'amount,asc' => 'Amount',
                    'amount,desc' => 'Amount reverse',
                    'date_paid,asc' => 'Date Paid',
                    'date_paid,desc' => 'Date Paid reverse',
                ],
                'default' => 'id,desc',
            ],
        ];
    }

    public function addSupportedFilters(array &$filters): void
    {
        parent::addSupportedFilters($filters);
        $filters['size']['default'] = Order::PER_PAGE;
    }

}
