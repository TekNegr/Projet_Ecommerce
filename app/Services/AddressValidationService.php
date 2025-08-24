<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressValidationService
{
    /**
     * Validate an address using Geoapify API
     *
     * @param string $zipCode
     * @param string $city
     * @param string $state
     * @param string $country
     * @return array
     */
    public function validateAddress(string $zipCode, string $city, string $state, string $country): array
    {
        // First, perform basic validation
        if (!$this->basicAddressValidation($zipCode, $city, $state, $country)) {
            return [
                'valid' => false,
                'message' => 'The provided address appears to be invalid. Please check your zip code, city, state, and country combination.'
            ];
        }

        // Then validate with Geoapify API
        return $this->validateWithGeoapify($zipCode, $city, $state, $country);
    }
    
    /**
     * Basic address validation logic
     *
     * @param string $zipCode
     * @param string $city
     * @param string $state
     * @param string $country
     * @return bool
     */
    protected function basicAddressValidation(string $zipCode, string $city, string $state, string $country): bool
    {
        // Basic validation rules
        if (empty($zipCode) || empty($city) || empty($state) || empty($country)) {
            return false;
        }
        
        // Validate zip code format (basic check)
        if (!preg_match('/^[A-Z0-9\- ]+$/i', $zipCode)) {
            return false;
        }
        
        // Validate city and state names (basic check)
        if (!preg_match('/^[A-Za-z\s\-\.\']+$/', $city) || !preg_match('/^[A-Za-z\s\-\.\']+$/', $state)) {
            return false;
        }
        
        // Validate country name (basic check)
        if (!preg_match('/^[A-Za-z\s\-\.\']+$/', $country)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate address using Geoapify API
     *
     * @param string $zipCode
     * @param string $city
     * @param string $state
     * @param string $country
     * @return array
     */
    protected function validateWithGeoapify(string $zipCode, string $city, string $state, string $country): array
    {
        $apiKey = config('services.geoapify.key');
        
        if (empty($apiKey)) {
            Log::warning('Geoapify API key is not configured. Skipping address validation.');
            return [
                'valid' => true,
                'message' => 'Address validation skipped (API key not configured)'
            ];
        }
        
        try {
            // Build the address string for Geoapify
            $addressString = implode(', ', array_filter([
                $zipCode,
                $city,
                $state,
                $country
            ]));
            
            $response = Http::get('https://api.geoapify.com/v1/geocode/search', [
                'text' => $addressString,
                'apiKey' => $apiKey,
                'format' => 'json',
                'limit' => 1
            ]);
            
            if (!$response->successful()) {
                Log::error('Geoapify API request failed: ' . $response->status());
                return [
                    'valid' => false,
                    'message' => 'Unable to validate address at this time. Please try again later.'
                ];
            }
            
            $data = $response->json();
            
            // Check if we got any results
            if (empty($data['features'])) {
                return [
                    'valid' => false,
                    'message' => 'The provided address could not be found. Please check your zip code, city, state, and country combination.'
                ];
            }
            
            $result = $data['features'][0]['properties'];
            
            // Validate that the returned address matches the input
            $isValid = $this->validateGeoapifyResult($result, $zipCode, $city, $state, $country);
            
            if ($isValid) {
                return [
                    'valid' => true,
                    'message'=>'Address validated successfully',
                    'data' => $result
                ];
            } else {
                return [
                    'valid' => false,
                    'message' => 'The provided address does not match our records. Please verify your address details.',
                    'suggested' => [
                        'zip_code' => $result['postcode'] ?? null,
                        'city' => $result['city'] ?? null,
                        'state' => $result['state'] ?? null,
                        'country' => $result['country'] ?? null
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Geoapify API error: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Address validation service is temporarily unavailable. Please try again later.'
            ];
        }
    }
    
    /**
     * Validate that the Geoapify result matches the input address
     *
     * @param array $result
     * @param string $zipCode
     * @param string $city
     * @param string $state
     * @param string $country
     * @return bool
     */
    protected function validateGeoapifyResult(array $result, string $zipCode, string $city, string $state, string $country): bool
    {
        // Check if the result has the expected confidence level
        $confidence = $result['rank']['confidence'] ?? 0;
        if ($confidence < 0.5) { // Adjust this threshold as needed
            return false;
        }
        
        // Compare zip code (postcode)
        if (isset($result['postcode']) && !empty($result['postcode'])) {
            $normalizedInputZip = strtoupper(preg_replace('/\s+/', '', $zipCode));
            $normalizedResultZip = strtoupper(preg_replace('/\s+/', '', $result['postcode']));
            
            if ($normalizedInputZip !== $normalizedResultZip) {
                return false;
            }
        }
        
        // Compare city
        if (isset($result['city']) && !empty($result['city'])) {
            $normalizedInputCity = strtoupper(trim($city));
            $normalizedResultCity = strtoupper(trim($result['city']));
            
            if ($normalizedInputCity !== $normalizedResultCity) {
                return false;
            }
        }
        
        // Compare state
        if (isset($result['state']) && !empty($result['state'])) {
            $normalizedInputState = strtoupper(trim($state));
            $normalizedResultState = strtoupper(trim($result['state']));
            
            if ($normalizedInputState !== $normalizedResultState) {
                return false;
            }
        }
        
        // Compare country
        if (isset($result['country']) && !empty($result['country'])) {
            $normalizedInputCountry = strtoupper(trim($country));
            $normalizedResultCountry = strtoupper(trim($result['country']));
            
            if ($normalizedInputCountry !== $normalizedResultCountry) {
                return false;
            }
        }
        
        return true;
    }
}
