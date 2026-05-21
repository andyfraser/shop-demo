<?php

namespace App\Services;

use App\Models\DeliveryOption;
use App\Repositories\DeliveryRepositoryInterface;
use Psr\Log\LoggerInterface;

class DeliveryService implements DeliveryServiceInterface {
    public function __construct(
        private DeliveryRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function all(): array {
        return $this->repository->getAll();
    }

    public function active(float $orderTotal = 0, ?string $userRole = null): array {
        return $this->repository->getActive($orderTotal, $userRole);
    }

    public function get(int $id): ?DeliveryOption {
        return $this->repository->findById($id);
    }

    public function save(array|DeliveryOption $data): bool {
        $id = is_object($data) ? $data->id : ($data['id'] ?? 0);
        $params = is_object($data) ? [
            'name'            => $data->name,
            'price'           => $data->price,
            'active'          => $data->active,
            'min_order_total' => $data->min_order_total,
            'target_role'     => $data->target_role,
        ] : [
            'name'            => $data['name'],
            'price'           => $data['price'],
            'active'          => $data['active'] ?? 0,
            'min_order_total' => $data['min_order_total'] ?? 0,
            'target_role'     => $data['target_role'] ?? null,
        ];

        return $this->repository->save($params, (int)$id);
    }

    public function delete(int $id): bool {
        return $this->repository->delete($id);
    }
}
