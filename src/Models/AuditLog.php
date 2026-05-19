<?php

namespace App\Models;

class AuditLog extends Model {
    public ?int $id = null;
    public ?int $user_id = null;
    public string $action = '';
    public ?string $resource_type = null;
    public ?string $resource_id = null;
    public ?string $details = null;
    public ?string $ip_address = null;
    public string $created_at = '';
}
