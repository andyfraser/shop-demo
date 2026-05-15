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

        return $addressId;
    }

    public function delete(int $id, int $userId): bool {
        return $this->repository->delete($id, $userId);
    }

    public function setDefault(int $id, int $userId): bool {
        return $this->repository->setDefault($id, $userId);
    }
}
