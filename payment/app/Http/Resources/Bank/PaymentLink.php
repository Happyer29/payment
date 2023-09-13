<?php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentLink extends JsonResource
{
    const PER_PAGE = 50;

    const STATUSES = [
        'new' => 'New',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ];

    private int $id;
    private int $order_id;
    private Order|null $order;
    private string $check_merchant_id;
    private float $amount;
    private string $card_type;
    private string $url;
    private string $transaction_id;
    private int|null $date_paid;
    private string $status;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->id = intval($resource['id'] ?? 0);
        $this->order_id = intval($resource['order_id']);
        $this->order = $resource['order'] ? Order::make($resource['order']) : null;
        $this->check_merchant_id = $resource['check_merchant_id'];
        $this->amount = floatval($resource['amount']);
        $this->card_type = $resource['card_type'];
        $this->url = $resource['url'];
        $this->transaction_id = $resource['transaction_id'] ?? '';
        $this->date_paid = $resource['date_paid'] ? intval($resource['date_paid']) : null;
        $this->status = $resource['status'];
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
            'order_id' => $this->order_id,
            'check_merchant_id' => $this->check_merchant_id,
            'amount' => $this->amount,
            'card_type' => $this->card_type,
            'url' => $this->url,
            'transaction_id' => $this->transaction_id,
            'date_paid' => $this->getDatePaid(),
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

    public function getOrderId(): int
    {
        return $this->order_id;
    }

    public function getOrder(): Order|null
    {
        return $this->order;
    }

    public function getShop(): Shop|null
    {
        return $this->order?->getShop();
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
