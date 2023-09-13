<?php

namespace App\Services\Bank;

use App\Http\Resources\Bank\BankMessage;
use App\Models\User;
use App\Services\BaseFilterService;
use Illuminate\Support\Facades\Http;

class BankMessageService extends BaseFilterService
{
    use BaseCrudService;

    private string $model = BankMessage::class;

    public function getMessage(int $id): ?BankMessage
    {
        $res = Http::get($this->crudUrl('/bank_message/read?id='.$id));
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $bank_message = $data['bank_message'] ?? null;
        if(!$bank_message){
            return null;
        }
        return BankMessage::make($bank_message);
    }

    public function approve(int $id, float $amount): array|null
    {
        if($amount <= 0){
            return null;
        }
        $res = Http::post($this->crudUrl('/bank_message/approve'), [
            'message_id' => $id,
            'amount' => $amount,
        ]);
        $data = json_decode($res->body(), true);

        return is_array($data) ? $data : null;
    }

    public function decline(int $id): array|null
    {
        $res = Http::post($this->crudUrl('/bank_message/decline'), [
            'message_id' => $id,
        ]);
        $data = json_decode($res->body(), true);

        return is_array($data) ? $data : null;
    }

    public function findMessages(int $page, int $size, array $filters): array|null
    {
        $page = max($page, 1);
        $size = max($size, 1);

        try{
            $url = "/bank_message/find?page=$page&size=$size";
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
            'bank_messages' => [],
        ];
        foreach($data['bank_messages'] ?? [] as $bank_message){
            $result['bank_messages'][] = BankMessage::make($bank_message);
        }
        return $result;
    }

    protected function buildFindDto(array $filters): array|null
    {
        $dto = [];

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

        // card_number
        if(isset($filters['card_number'])){
            $value = $filters['card_number']['value'] ?? null;
            if(is_string($value) and strlen(trim($value)) > 0){
                $dto['card_number'] = trim($value);
            }
        }

        // message_id
        if(isset($filters['message_id'])){
            $message_ids = $filters['message_id']['value'] ?? null;
            if(is_string($message_ids)){
                $message_ids = preg_split('/\s*,\s*/', $message_ids);
                $message_ids = array_map('intval', $message_ids);
                foreach($message_ids as $id){
                    if($id > 0){
                        $dto['id'][] = $id;
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
                        $dto['order_id'][] = $id;
                    }
                }
            }
        }

        // order_id
        if(isset($filters['shop_id'])){
            $shop_ids = $filters['shop_id']['value'] ?? null;
            if(is_string($shop_ids)){
                $shop_ids = preg_split('/\s*,\s*/', $shop_ids);
                $shop_ids = array_map('intval', $shop_ids);
                foreach($shop_ids as $id){
                    if($id > 0){
                        $dto['shop_id'][] = $id;
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
            'message_id' => [
                'label' => 'Message id',
                'type' => 'text',
            ],
            'order_id' => [
                'label' => 'Order id',
                'type' => 'text',
            ],
            'shop_id' => [
                'label' => 'Shop id',
                'type' => 'text',
            ],
            'card_number' => [
                'label' => 'Card number',
                'type' => 'text',
                'can' => ['viewAny', BankMessage::class],
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    'all' => 'Show all',
                    'success,declined,error' => 'Finished',
                    'success,declined' => 'Success/Declined',
                    BankMessage::STATUS_PENDING_APPROVAL => 'Pending approval',
                    BankMessage::STATUS_DECLINED => 'Declined',
                    BankMessage::STATUS_SUCCESS => 'Successful',
                    BankMessage::STATUS_ERROR => 'Errored',
                    BankMessage::STATUS_NEW => 'New',
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
                    'card_number,asc' => 'Card number A-Z',
                    'card_number,desc' => 'Card number Z-A',
                    'amount,asc' => 'Amount',
                    'amount,desc' => 'Amount reverse',
                    'error,asc' => 'Error',
                    'error,desc' => 'Error reverse',
                    'created_at,asc' => 'Date',
                    'created_at,desc' => 'Date reverse',
                ],
                'default' => 'id,desc',
            ],
        ];
    }

    public function addSupportedFilters(array &$filters): void
    {
        parent::addSupportedFilters($filters);
        $filters['size']['default'] = BankMessage::PER_PAGE;
    }

}
