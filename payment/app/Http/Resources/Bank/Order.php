<?php

namespace App\Http\Resources\Bank;

use App\Services\Bank\ShopService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Order extends JsonResource
{
    const PER_PAGE = 20;
    const STATUSES = [
        'new' => 'New',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ];

    const PAYMENT_METHODS = [
        'bank_transfer' => 'Bank transfer',
        'kapital_bank' => 'Kapital Bank',
    ];

    private int $id;
    private int $shop_id;
    private Shop|null $shop;
    private int $owner_id;
    private string $number;
    private string $payment_method;
    private int $card_id;
    private float $amount;
    private int|null $date_paid;
    private string $payload;
    private string $status;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->id = intval($resource['id'] ?? 0);
        $this->shop_id = intval($resource['shop_id']);
        $this->shop = $resource['shop'] ? Shop::make($resource['shop']) : null;
        $this->owner_id = intval($resource['owner_id']);
        $this->number = $resource['number'] ?? '';
        $this->payment_method = $resource['payment_method'];
        $this->card_id = intval($resource['card_id']);
        $this->amount = floatval($resource['amount']);
        $this->date_paid = $resource['date_paid'] ? intval($resource['date_paid']) : null;
        $this->payload = $resource['payload'] ?? '';
        $this->status = $resource['status'] ?? '';
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'owner_id' => $this->owner_id,
            'number' => $this->number,
            'payment_method' => $this->payment_method,
            'card_id' => $this->card_id,
            'amount' => $this->amount,
            'date_paid' => $this->getDatePaid(),
            'payload' => $this->payload,
            'status' => $this->status,
        ];
    }

    public function getDatePaid(): string|null
    {
        try{
            if(!$this->resource['date_paid']){
                return null;
            }
            $stamp = intval($this->resource['date_paid']);
            return date('d.m.Y H:i:sP', $stamp);
        }catch (\Throwable $exception){
            return null;
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getShopId(): int
    {
        return $this->shop_id;
    }

    public function getShop(): Shop|null
    {
        return $this->shop;
    }

    public function getOwnerId(): int
    {
        return $this->owner_id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }
}
