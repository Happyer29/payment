<?php

namespace App\Services\Bank;

use App\Http\Resources\Bank\Withdraw;
use App\Models\User;
use App\Services\BaseFilterService;
use Illuminate\Support\Facades\Http;

class WithdrawService extends BaseFilterService
{

    use BaseCrudService;

    public function getWithdraw(int $id): ?Withdraw
    {
        $res = Http::get($this->crudUrl('/withdraw/read?id='.$id));
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $wd = $data['withdraw'] ?? null;
        if(!$wd){
            return null;
        }
        return Withdraw::make($wd);
    }

    public function updateWithdraw(array $wd): ?Withdraw
    {
        $res = Http::post($this->crudUrl('/withdraw/update'), $wd);
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $wd = $data['withdraw'] ?? null;
        if(!$wd){
            return null;
        }
        return Withdraw::make($wd);
    }

    public function approve(int $id): array|null
    {
        $res = Http::post($this->crudUrl('/withdraw/approve'), [
            'withdraw_id' => $id,
        ]);
        $data = json_decode($res->body(), true);

        return is_array($data) ? $data : null;
    }

    public function decline(int $id): array|null
    {
        $res = Http::post($this->crudUrl('/withdraw/decline'), [
            'withdraw_id' => $id,
        ]);
        $data = json_decode($res->body(), true);

        return is_array($data) ? $data : null;
    }

    public function process(int $id): array|null
    {
        $res = Http::post($this->crudUrl('/withdraw/process'), [
            'withdraw_id' => $id,
        ]);
        $data = json_decode($res->body(), true);

        return is_array($data) ? $data : null;
    }

    public function findWithdraws(int $page, int $size, array $filters): array|null
    {
        $page = max($page, 1);
        $size = max($size, 1);

        try{
            $url = "/withdraw/find?page=$page&size=$size";
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
            'withdraws' => [],
        ];
        foreach($data['withdraws'] ?? [] as $wd){
            $result['withdraws'][] = Withdraw::make($wd);
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
        $viewAny = $user?->can('viewAny', Withdraw::class);
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

        // type
        if(isset($filters['type'])){
            $value = $filters['type']['value'] ?? null;
            $options = $filters['type']['options'];
            $default = $filters['type']['default'] ?? null;

            if(isset($options[$value]) and $value !== $default){
                $dto['type'] = explode(',', $value);
            }else{
                $dto['type'] = [];
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
                    if($id > 0){
                        $dto['shop_id'][] = $id;
                    }
                }
            }
        }

        // withdraw_id
        if(isset($filters['withdraw_id'])){
            $withdraw_ids = $filters['withdraw_id']['value'] ?? null;
            if(is_string($withdraw_ids)){
                $withdraw_ids = preg_split('/\s*,\s*/', $withdraw_ids);
                $withdraw_ids = array_map('intval', $withdraw_ids);
                foreach($withdraw_ids as $id){
                    if($id > 0){
                        $dto['id'][] = $id;
                    }
                }
            }
        }

        // number
        if(isset($filters['number'])){
            $number = $filters['number']['value'] ?? null;
            $dto['number'] = is_string($number) ? $number : '';
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
            'withdraw_id' => [
                'label' => 'Withdraw id',
                'type' => 'text',
            ],
            'number' => [
                'label' => 'Withdraw number',
                'type' => 'text',
            ],
            'owner_email' => [
                'label' => 'Owner email',
                'type' => 'text',
                'can' => ['viewAny', Withdraw::class],
            ],
            'shop_id' => [
                'label' => 'Shop id',
                'type' => 'text',
            ],
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'options' => [
                    'all' => 'Show all',
                    'm10' => 'M10 Kapital Bank',
                    'card' => 'Card',
                ],
                'default' => 'all',
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    'all' => 'Show all',
                    implode(',', [Withdraw::STATUS_APPROVED, Withdraw::STATUS_DECLINED]) => 'Approved/Declined',
                    Withdraw::STATUS_PENDING => 'Pending',
                    Withdraw::STATUS_PROCESSING => 'Processing',
                    Withdraw::STATUS_APPROVED => 'Approved',
                    Withdraw::STATUS_DECLINED => 'Declined',
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
                    'type,asc' => 'Type',
                    'type,desc' => 'Type reverse',
                    'number,asc' => 'Number A-Z',
                    'number,desc' => 'Number Z-A',
                    'amount,asc' => 'Amount',
                    'amount,desc' => 'Amount reverse',
                    'created_at,asc' => 'Date Created',
                    'created_at,desc' => 'Date Created reverse',
                    'updated_at,asc' => 'Last update',
                    'updated_at,desc' => 'Last update reverse',
                    'finished_at,asc' => 'Date Finished',
                    'finished_at,desc' => 'Date Finished reverse',
                ],
                'default' => 'id,desc',
            ],
        ];
    }

    public function addSupportedFilters(array &$filters): void
    {
        parent::addSupportedFilters($filters);
        $filters['size']['default'] = Withdraw::PER_PAGE;
    }

}
