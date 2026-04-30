<?php

namespace App\Models;

class Review extends Model {
    public int $id;
    public int $product_id;
    public int $user_id;
    public int $rating;
    public ?string $comment = null;
    public string $status;
    public string $created_at;

    // Join fields
    public ?string $user_name = null;
    public ?string $product_name = null;

    public function getStarRating(): string {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    public function getStatusBadgeClass(): string {
        return match ($this->status) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default    => 'badge-warning',
        };
    }

    public function isApproved(): bool {
        return $this->status === 'approved';
    }

    public function isPending(): bool {
        return $this->status === 'pending';
    }

    public function isRejected(): bool {
        return $this->status === 'rejected';
    }
}
