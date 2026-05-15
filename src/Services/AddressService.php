<?php
namespace App\Services;

use App\Models\UserAddress;
use App\Repositories\AddressRepositoryInterface;
use Psr\Log\LoggerInterface;

class AddressService implements AddressServiceInterface {
    public function __construct(
        private AddressRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    /**
     * @return UserAddress[]
     */
    public function getByUserId(int $userId): array {
        return $this->repository->getByUserId($userId);
    }

    public function findById(int $id): ?UserAddress {
        return $this->repository->findById($id);
    }

    public function save(int $userId, array $data, int $id = 0): int {
        $addressId = $this->repository->save($userId, $data, $id);

        if (!empty($data['is_default'])) {
            $this->setDefault($addressId, $userId);
        }

        $this->logger->info("User {userId} {action} address {id} ({label})", [
            'userId' => $userId,
            'id' => $addressId,
            'label' => $data['label'] ?? 'No label',
            'action' => $id > 0 ? 'updated' : 'added'
        ]);

        return $addressId;
    }

    public function delete(int $id, int $userId): bool {
        $deleted = $this->repository->delete($id, $userId);
        if ($deleted) {
            $this->logger->info("User {userId} deleted address {id}", [
                'userId' => $userId,
                'id' => $id
            ]);
        }
        return $deleted;
    }

    public function setDefault(int $id, int $userId): bool {
        $result = $this->repository->setDefault($id, $userId);
        if ($result) {
            $this->logger->info("User {userId} set address {id} as default", [
                'userId' => $userId,
                'id' => $id
            ]);
        }
        return $result;
    }
}
