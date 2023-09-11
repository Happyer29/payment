<?php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Card extends JsonResource
{
    const TypeWithNumber = "with_number";
    const TypeWithPhone = "with_phone";

    private int $id;
    private string $type;
    private float|null $balance;
    private string|null $phonePrefix;
    private string|null $phoneNumber;
    private string|null $cardNumber;
    private string $status;

    public function __construct($resource)
    {
        parent::__construct($resource);

        $balance = $resource['balance'] ?? null;
        $balance = is_numeric($balance) ? floatval($balance) : null;

        $this->id = intval($resource['id'] ?? 0);
        $this->type = $resource['type'];
        $this->balance = $balance;
        $this->phonePrefix = $resource['phone_prefix'] ?? null;
        $this->phoneNumber = $resource['phone_number'] ?? null;
        $this->cardNumber = $resource['card_number'] ?? null;
        $this->status = $resource['status'] ?? "disabled";
    }

    const PER_PAGE = 99999;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? 0,
            'type' => $this->type,
            'balance' => $this->balance,
            'phone_prefix' => $this->phonePrefix,
            'phone_number' => $this->phoneNumber,
            'card_number' => $this->cardNumber,
            'status' => $this->status ?? "",
        ];
    }

    public function getId(): int {return $this->id;}

    public function getPhone(): string|null
    {
        if(!$this->phoneNumber or !$this->phonePrefix){
            return null;
        }
        return $this->phonePrefix . $this->phoneNumber;
    }

    public function getBalance(): float|null
    {
        return $this->balance;
    }

    public function getCardNumber(): string|null
    {
        return $this->cardNumber;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    public function __toString(): string
    {
        $id = $this->getId();
        $number = $this->isType(self::TypeWithPhone) ? $this->getPhone() : $this->getCardNumber();
        return "№$id: $number";
    }
}
