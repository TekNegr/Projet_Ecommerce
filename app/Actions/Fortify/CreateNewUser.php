<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash; 
use App\Services\AddressValidationService; // Import the AddressValidationService
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'role' => ['required', 'in:admin,seller,customer'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'], // Add country validation
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        // Validate the address
        $addressValidationService = new AddressValidationService(); // Ensure the service is instantiated
        $addressValidation = $addressValidationService->validateAddress(
            $input['zip_code'] ?? '',
            $input['city'] ?? '',
            $input['state'] ?? '',
            $input['country'] ?? ''
        );

        if (!$addressValidation['valid']) {
            throw new \Exception($addressValidation['message']);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'zip_code' => $input['zip_code'] ?? null,
            'city' => $input['city'] ?? null,
            'state' => $input['state'] ?? null,
            'country' => $input['country'] ?? null, // Save country
        ]);

        // Assign role based on selection
        $user->assignRole($input['role']);

        return $user;
    }
}
