<?php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use function Webmozart\Assert\Tests\StaticAnalysis\float;

class BankMessage extends JsonResource
{
    const STATUS_NEW = 'new';
    const STATUS_ERROR = 'error';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_DECLINED = 'declined';
    const STATUS_SUCCESS = 'success';

    const PER_PAGE = 10;


    private int $id;
    private int $orderId;
    private int $shopId;
    private int $createdAt;
    private string|null $senderPhone;
    private string|null $receiverPhone;
    private string $rawMessage;
    private string $cardNumber;
    private float $amount;
    private string $error;
    private string $status;


    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->id = intval($resource['id']);
        $this->orderId = intval($resource['order_id'] ?? 0);
        $this->shopId = intval($resource['shop_id'] ?? 0);
        $this->createdAt = intval($resource['created_at'] ?? null);
        $this->senderPhone = $resource['sender_phone'] ?? null;
        $this->receiverPhone = $resource['receiver_phone'] ?? null;
        $this->rawMessage = $resource['raw_message'] ?? '';
        $this->cardNumber = $resource['card_number'] ?? '';
        $this->amount = floatval($resource['amount'] ?? null);
        $this->error = $resource['error'] ?? '';
        $this->status = $resource['status'] ?? '';
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getSenderPhone(): ?string
    {
        return $this->senderPhone;
    }

    public function getReceiverPhone(): ?string
    {
        return $this->receiverPhone;
    }

    public function getRawMessage(): string
    {
        return $this->rawMessage;
    }

    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function isWaitingForApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function getFormattedStatus(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'New',
            self::STATUS_ERROR => 'Error',
            self::STATUS_PENDING_APPROVAL => 'Pending approval',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_SUCCESS => 'Success',

            default => '?',
        };
    }
}
