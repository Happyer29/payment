<?php

namespace App\Services\Bank;

use App\Http\Resources\Bank\Card;
use Illuminate\Support\Facades\Http;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class CardService
{
    use BaseCrudService;

    public function getCards(int $page, int $size): ?array
    {
        $page = max($page, 1);
        $size = max($size, 1);

        try{
            $res = Http::get($this->crudUrl("/card/list?page=$page&size=$size"));
            if($res->status() !== 200){
                return null;
            }
            $data = json_decode($res->body(), true);
        }catch (\Throwable $ex){
            return null;
        }

        $result = [
            'total' => intval($data['total'] ?? 0),
            'cards' => [],
        ];
        foreach($data['cards'] ?? [] as $card){
            $result['cards'][] = Card::make($card);
        }
        return $result;
    }

    public function getCard(int $id): Card|null
    {
        $res = Http::get($this->crudUrl('/card/read?id='.$id));
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $card = $data['card'] ?? null;
        if(!$card){
            return null;
        }
        return Card::make($card);
    }

    public function createCard(array $card): Card|null
    {
        $res = Http::post($this->crudUrl('/card/create'), $card);
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $card = $data['card'] ?? null;
        if(!$card){
            return null;
        }
        return Card::make($card);
    }

    public function updateCard(array $card): Card|null
    {
        $res = Http::post($this->crudUrl('/card/update'), $card);
        if($res->status() !== 200){
            return null;
        }
        $data = json_decode($res->body(), true);

        $card = $data['card'] ?? null;
        if(!$card){
            return null;
        }
        return Card::make($card);
    }

    public function deleteCard(int $id): string|null
    {
        $res = Http::post($this->crudUrl('/card/delete?id='.$id));
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

    public function checkActivity(array $ids): ?array
    {
        $res = Http::get($this->crudUrl('/card/check-activity?ids='.implode(',', $ids)));
        if($res->status() !== 200){
            return null;
        }
        try{
            return json_decode($res->body(), true);
        }catch (\Throwable $exception){
            return null;
        }
    }

    public function changeBalance(int $card_id, string $operation, float $amount): array
    {
        if($amount <= 0 or $card_id <= 0 or !in_array($operation, ['increase', 'decrease'])){
            return ['success' => false];
        }

        $data = [
            'card_id' => $card_id,
            'amount' => $amount,
            'operation' => $operation,
        ];
        $res = Http::post($this->crudUrl('/card/change-balance'), $data);
        try{
            $data = json_decode($res->body(), true);
        }catch (\Throwable $ex){
            return ['success' => false, 'error' => 'Unable to handle response'];
        }

        $success = $data['success'] ?? null;
        if(!$success){
            return $data['error'] ?? "Error while deleting: invalid response.";
        }
        return ['success' => true];
    }
}
