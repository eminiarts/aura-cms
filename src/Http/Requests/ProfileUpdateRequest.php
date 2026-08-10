<?php

namespace Aura\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $user = $this->user();
        $connectionName = $user->getConnectionName();
        $usersTable = ($connectionName ? $connectionName.'.' : '').$user->getTable();

        return [
            'name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique($usersTable)->ignore($user->getKey())],
        ];
    }
}
