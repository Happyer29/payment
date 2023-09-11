<?php

namespace App\Services\Bank;

use App\Http\Resources\Bank\Shop;
use App\Models\User;
use App\Services\BaseFilterService;
use Illuminate\Support\Facades\Http;
use function Webmozart\Assert\Tests\StaticAnalysis\email;

class ShopService extends BaseFilterService
{
    use BaseCrudService;

    private string $model = Shop::class;

    public function getShops(int $page, int $size, int $owner_id = 0): ?array
    {
        $page = max($page, 1);
        $size = max($size, 1);

        try{
            $owner_id = max(0, $owner_id);
            $url = "/shop/list?page=$page&size=$size&owner_id=$owner_id";
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
            'shops' => [],
        ];
        foreach($data['shops'] ?? [] as $shop){
            $result['shops'][] = Shop::make($shop)->resolve();
        }
        return $result;
    }

    public function findShops(int $page, int $size, array $filters): ?array
    {
        $page = max($page, 1);
        $size = max($size, 1);

        try{
            $url = "/shop/find?page=$page&size=$size";
            $dto = $this->buildFindDto($filters);
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
            'shops' => [],
        ];
        foreach($data['shops'] ?? [] as $shop){
            $result['shops'][] = Shop::make($shop);
        }
        return $result;
    }

    public function getShop(int $id): ?Shop
    {
        $res = Http::get($this->crudUrl('/shop/read?id='.$id));
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $shop = $data['shop'] ?? null;
        if(!$shop){
            return null;
        }
        return Shop::make($shop);
    }

    public function createShop(array $shop): array
    {
        try{
            $res = Http::post($this->crudUrl('/shop/create'), $shop);
            $data = json_decode($res->body(), true);
            $shop = $data['shop'] ?? null;
            if(!$shop){
                return ['error' => $data['error'] ?? 'Something went wrong...'];
            }
            return ['shop' => Shop::make($shop)];
        }catch (\Throwable $ex){
            return ['error' => 'Unexpected error while creating order.'];
        }
    }

    public function updateShop(array $shop): ?Shop
    {
        $res = Http::post($this->crudUrl('/shop/update'), $shop);
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $shop = $data['shop'] ?? null;
        if(!$shop){
            return null;
        }
        return Shop::make($shop);
    }

    public function regeneratePrivateKey(int $shop_id): array
    {
        $res = Http::post($this->crudUrl('/shop/regenerate-private-key?shop_id='.$shop_id));
        if($res->status() !== 200){
            return [
                'error' => "Error while changing private key.",
            ];
        }
        $data = json_decode($res->body(), true);

        $success = $data['success'] ?? null;
        if(!$success){
            return [
                'error' => $data['error'] ?? "Error while changing private key.",
            ];
        }
        return [
            'private_key' => $res['private_key'] ?? null,
            'message' => $res['message'] ?? '',
        ];
    }

    public function validateHost(int $shop_id): array
    {
        $res = Http::post($this->crudUrl('/shop/validate-host?shop_id='.$shop_id));
        if($res->status() !== 200){
            return [
                'error' => "Error while validating shop host.",
            ];
        }
        $data = json_decode($res->body(), true);

        $success = $data['success'] ?? null;
        if(!$success){
            return [
                'error' => $data['error'] ?? "Error while validating.",
            ];
        }
        return [
            'message' => $res['message'] ?? '',
        ];
    }

    public function deleteShop(int $id): string|null
    {
        $res = Http::post($this->crudUrl('/shop/delete?id='.$id));
        if($res->status() !== 200){
            return "Error while deleting.";
        }
        $data = json_decode($res->body(), true);

        $success = $data['success'] ?? null;
        if(!$success){
            return $data['error'] ?? "Error while deleting: invalid response.";
        }
        return null;
    }

    public function deactivateShops(array $shop_ids): array
    {
        $res = [
            'count' => 0,
            'errors' => [],
        ];
        foreach($shop_ids as $id){
            $res['count']++;
            $shop = $this->getShop($id);
            if(!$shop){
                $res['errors'][] = "Shop #$id not found.";
                continue;
            }
            $shop->deactivate();
            if(!$this->updateShop($shop->resolve())){
                $res['errors'][] = "Error while saving shop #$id.";
            }
        }
        return $res;
    }

    public function activateShopsByModerationStatus(array $shop_ids, bool|null $moderated): array
    {
        $res = [
            'count' => 0,
            'errors' => [],
        ];
        foreach($shop_ids as $id){
            $shop = $this->getShop($id);
            if(!$shop){
                $res['errors'][] = "Shop #$id not found.";
                continue;
            }
            if(!is_null($moderated) and $shop->isModerated() !== $moderated){
                continue;
            }
            $shop->activate();
            if(!$this->updateShop($shop->resolve())){
                $res['errors'][] = "Error while saving shop #$id.";
            }
            $res['count']++;
        }
        return $res;
    }

    protected function buildFindDto(array $filters): array|null
    {
        $dto = [];

        $user = auth()?->user();
        if(!$user->id){
            return null;
        }

        // owner_id
        $viewAny = $user?->can('viewAny', Shop::class);
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

        // is_active
        if(isset($filters['is_active'])){
            $dto['active'] = match ($filters['is_active']['value']){
                'active' => true,
                'inactive' => false,
                default => null,
            };
        }

        // is_active
        if(isset($filters['is_moderated'])){
            $dto['moderated'] = match ($filters['is_moderated']['value']){
                'yes' => true,
                'no' => false,
                default => null,
            };
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
            'shop_id' => [
                'label' => 'Shop id',
                'type' => 'text',
            ],
            'owner_email' => [
                'label' => 'Owner email',
                'type' => 'text',
                'can' => ['viewAny', Shop::class],
            ],
            'is_active' => [
                'label' => 'Activity',
                'type' => 'select',
                'options' => [
                    'all' => 'Show all',
                    'active' => 'Only active',
                    'inactive' => 'Only inactive',
                ],
                'default' => 'all',
            ],
            'is_moderated' => [
                'label' => 'Moderation',
                'type' => 'select',
                'options' => [
                    'all' => 'Show all',
                    'yes' => 'Only moderated',
                    'no' => 'Only not moderated',
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
                    'name,asc' => 'Name A-Z',
                    'name,desc' => 'Name Z-A',
                ],
                'default' => 'id,desc',
            ],
        ];
    }

    public function addSupportedFilters(array &$filters): void
    {
        parent::addSupportedFilters($filters);
        $filters['size']['default'] = Shop::PER_PAGE;
    }

    public function getDefaultBulkActions(): array
    {
        return [
            'deactivate' => [
                'label' => 'Deactivate all',
                'can' => ['moderateAny', Shop::class],
            ],
            'activate_all' => [
                'label' => 'Activate all',
                'can' => ['moderateAny', Shop::class],
            ],
            'activate_unmoderated' => [
                'label' => 'Activate only unmoderated',
                'can' => ['moderateAny', Shop::class],
            ],
        ];
    }

}
