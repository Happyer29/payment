<?php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Withdraw extends JsonResource
{

    const PER_PAGE = 40;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'wait_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_DECLINED = 'declined';

    const STATUSES = [
        Withdraw::STATUS_PENDING => 'Pending',
        Withdraw::STATUS_PROCESSING => 'Processing',
        Withdraw::STATUS_APPROVED => 'Approved',
        Withdraw::STATUS_DECLINED => 'Declined',
    ];

    const TYPE_M10 = 'm10';
    const TYPE_CARD = 'card';

    const TYPES = [
        self::TYPE_CARD => 'Card',
        self::TYPE_M10 => 'M10 Kapital Bank',
    ];

    private int $id;
    private string $number;
    private int $shop_id;
    private Shop $shop;
    private string $type;
    private string $card_number;
    private string $card_expiration_date;
    private string $phone;
    private float $amount;
    private string $status;
    private int $created_at;
    private int|null $updated_at;
    private int|null $finished_at;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->id = intval($resource['id'] ?? 0);
        $this->number = $resource['number'] ?? '';
        $this->shop_id = intval($resource['shop_id'] ?? 0);
        $this->shop = Shop::make($resource['shop']);
        $this->type = $resource['type'] ?? '';
        $this->card_number = $resource['card_number'] ?? '';
        $this->card_expiration_date = $resource['card_expiration_date'] ?? '';
        $this->phone = $resource['phone'] ?? '';
        $this->amount = floatval($resource['amount'] ?? 0);
        $this->status = $resource['status'] ?? '';
        $this->created_at = intval($resource['created_at'] ?? 0);
        $this->updated_at = intval($resource['updated_at'] ?? 0);
        if($this->updated_at <= $this->created_at){
            $this->updated_at = null;
        }
        $this->finished_at = $resource['finished_at'] ? intval($resource['finished_at']) : null;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return int
     */
    public function getShopId(): int
    {
        return $this->shop_id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getCardNumber(): string
    {
        return $this->card_number;
    }

    /**
     * @return string
     */
    public function getCardExpirationDate(): string
    {
        return $this->card_expiration_date;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    /**
     * @return int|null
     */
    public function getFinishedAt(): ?int
    {
        return $this->finished_at;
    }

    /**
     * @return Shop
     */
    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function getRecipient(): string
    {
        if($this->type === self::TYPE_M10){
            return $this->phone;
        }elseif($this->type === self::TYPE_CARD){
            return $this->card_number . ' (' . $this->card_expiration_date . ')';
        }else{
            return '';
        }
    }

    public function isWaitingForApproval(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isWaitingForProcessing(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * @return int|null
     */
    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }
}
