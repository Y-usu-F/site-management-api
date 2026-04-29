<?php

namespace App\Core;

use App\Exceptions\ValidationApiException;

class ApiValidator
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $rules
     * @return array<string, mixed>
     */
    public function validateOrFail(array $payload, array $rules): array
    {
        $validation = service('validation');
        $validation->reset();
        $validation->setRules($rules);

        if (! $validation->run($payload)) {
            throw new ValidationApiException('Gonderilen veri gecersiz', $validation->getErrors());
        }

        return $payload;
    }
}
