<?php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Shop extends JsonResource
{
    const PER_PAGE = 20;

    private int $id;
    private string $name;
    private string $host;
    private int $owner_id;
    private bool $active;
    private bool $host_validated;
    private bool $moderated;
    private string $public_key;
    private string $confirm_code;
    private array $webhooks;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->id = intval($resource['id'] ?? 0);
        $this->name = $resource['name'];
        $this->host = $resource['host'];
        $this->owner_id = $resource['owner_id'];
        $this->active = ($resource['active'] ?? false) === true;
        $this->host_validated = ($resource['host_validated'] ?? false) === true;
        $this->moderated = ($resource['moderated'] ?? false) === true;
        $this->public_key = $resource['public_key'] ?? '';
        $this->confirm_code = $resource['confirm_code'] ?? '';
        $this->webhooks = $resource['webhooks'] ?? [];
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
            'name' => $this->name,
            'host' => $this->host,
            'owner_id' => $this->owner_id,
            'active' => $this->active,
            'host_validated' => $this->host_validated,
            'moderated' => $this->moderated,
            'public_key' => $this->public_key,
            'confirm_code' => $this->confirm_code,
            'webhooks' => !empty($this->webhooks) ? $this->webhooks : null,
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getOwnerId(): int
    {
        return $this->owner_id;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isModerated(): bool
    {
        return $this->moderated;
    }

    public function setModerated(bool $value): void
    {
        $this->moderated = $value;
    }

    public function isHostValidated(): bool
    {
        return $this->host_validated;
    }

    public function getConfirmCode(): string
    {
        return $this->confirm_code;
    }

    public function getWebhook(string $name): string|null
    {
        return $this->webhooks[$name] ?? null;
    }

    public function deactivate(): void
    {
        $this->moderated = false;
        $this->active = false;
    }

    public function activate(): void
    {
        $this->moderated = true;
        $this->active = true;
    }
}
