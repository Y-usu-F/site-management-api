<?php

namespace App\Services\Profile;

use App\Core\BaseService;
use App\Exceptions\NotFoundApiException;
use App\Models\UserModel;

class ProfileService extends BaseService
{
    public function __construct(
        private readonly UserModel $userModel = new UserModel()
    ) {
        parent::__construct();
    }

    public function show(int $userId): array
    {
        $profile = $this->userModel->getSafeProfile($userId);
        if ($profile === null) {
            throw new NotFoundApiException('Profil bulunamadi');
        }

        return $profile;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(int $userId, array $payload): array
    {
        $safeInput = [
            'first_name' => isset($payload['first_name']) ? trim((string) $payload['first_name']) : null,
            'last_name' => isset($payload['last_name']) ? trim((string) $payload['last_name']) : null,
        ];

        $safeInput = array_filter($safeInput, static fn ($value): bool => $value !== null && $value !== '');
        $this->userModel->updateProfileByWhitelist($userId, $safeInput);

        $this->audit('profile.update.success', [
            'status' => 'success',
            'target_user_id' => $userId,
            'entity_type' => 'user',
            'entity_id' => $userId,
            'meta' => ['updated_fields' => array_keys($safeInput)],
        ]);

        return $this->show($userId);
    }
}
