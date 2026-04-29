<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Profile\ChangePasswordService;
use App\Services\Profile\ProfileService;
use App\Validation\ProfileValidation;
use Throwable;

class ProfileController extends ApiController
{
    public function __construct(
        private readonly ProfileService $profileService = new ProfileService(),
        private readonly ChangePasswordService $changePasswordService = new ChangePasswordService()
    ) {
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Profil getirildi', $this->profileService->show($this->resolveUserId()));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function update($id = null)
    {
        try {
            $payload = $this->apiValidator->validateOrFail(
                $this->request->getJSON(true) ?? [],
                ProfileValidation::updateRules()
            );

            return $this->ok('Profil guncellendi', $this->profileService->update($this->resolveUserId(), $payload));
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    public function changePassword()
    {
        try {
            $payload = $this->apiValidator->validateOrFail(
                $this->request->getJSON(true) ?? [],
                ProfileValidation::changePasswordRules()
            );

            $result = $this->changePasswordService->changePassword(
                userId: $this->resolveUserId(),
                currentPassword: (string) $payload['current_password'],
                newPassword: (string) $payload['new_password'],
                currentSessionId: $this->resolveSessionId()
            );

            return $this->ok('Sifre degistirildi', $result);
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }

    private function resolveUserId(): int
    {
        return (int) ($this->request->user?->id ?? 0);
    }

    private function resolveSessionId(): ?int
    {
        $requestData = get_object_vars($this->request);
        return isset($requestData['session_id']) ? (int) $requestData['session_id'] : null;
    }
}
