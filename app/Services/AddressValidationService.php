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
        Log::info("Performing basic validation for address: $zipCode, $city, $state, $country");
        if (!$this->basicAddressValidation($zipCode, $city, $state, $country)) {
            return [
                'valid' => false,
                'message' => 'The provided address appears to be invalid. Please check your zip code, city, state, and country combination.'
            ];
        }

        // Then validate with Geoapify API
        Log::info("Basic validation passed. Proceeding to Geoapify validation.");
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
        Log::info("Starting basic validation for: $zipCode, $city, $state, $country");
        // Basic validation rules
        if (empty($zipCode) || empty($city) || empty($state) || empty($country)) {
            return false;
        }
        Log::info("All address fields are present.");
        // Validate zip code format (basic check)
        if (!preg_match('/^[A-Z0-9\- ]+$/i', $zipCode)) {
            return false;
        }
        Log::info("Zip code format is valid.");
        // Validate city and state names (basic check) - allow accented characters
        if (!preg_match('/^[A-Za-zÀ-ÿ\s\-\.\']+$/', $city) || !preg_match('/^[A-Za-zÀ-ÿ\s\-\.\']+$/', $state)) {
            return false;
        }
        Log::info("City and state format are valid.");
        // Validate country name (basic check) - allow accented characters
        if (!preg_match('/^[A-Za-zÀ-ÿ\s\-\.\']+$/', $country)) {
            return false;
        }
        Log::info("Country format is valid.");  
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
        Log::info('Starting Geoapify address validation.');
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
            Log::info('Validating address with Geoapify: ' . $addressString);
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
            Log::info('Geoapify response: ' . json_encode($data));
            // Check if we got any results - Geoapify uses 'results' not 'features'
            if (empty($data['results'])) {
                return [
                    'valid' => false,
                    'message' => 'The provided address could not be found. Please check your zip code, city, state, and country combination.'
                ];
            }
            
            $result = $data['results'][0];
            Log::info('Validating results exactness.');
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
        Log::info("Geoapify result confidence level: $confidence");
        // Compare zip code (postcode)
        if (isset($result['postcode']) && !empty($result['postcode'])) {
            $normalizedInputZip = strtoupper(preg_replace('/\s+/', '', $zipCode));
            $normalizedResultZip = strtoupper(preg_replace('/\s+/', '', $result['postcode']));
            
            if ($normalizedInputZip !== $normalizedResultZip) {
                return false;
            }
        }
        Log::info("Zip code matches.");
        // Compare city
        if (isset($result['city']) && !empty($result['city'])) {
            $normalizedInputCity = $this->normalizeString(trim($city));
            $normalizedResultCity = $this->normalizeString(trim($result['city']));
            
            if ($normalizedInputCity !== $normalizedResultCity) {
                return false;
            }
        }
        Log::info("City matches.");
        // Compare state
        if (isset($result['state']) && !empty($result['state'])) {
            $normalizedInputState = $this->normalizeString(trim($state));
            $normalizedResultState = $this->normalizeString(trim($result['state']));
            
            if ($normalizedInputState !== $normalizedResultState) {
                return false;
            }
        }
        Log::info("State matches.");
        // Compare country
        if (isset($result['country']) && !empty($result['country'])) {
            $normalizedInputCountry = $this->normalizeString(trim($country));
            $normalizedResultCountry = $this->normalizeString(trim($result['country']));
            
            if ($normalizedInputCountry !== $normalizedResultCountry) {
                return false;
            }
        }
        Log::info("Country matches.");
        return true;
    }
    
    /**
     * Normalize string by removing accents and converting to uppercase
     *
     * @param string $string
     * @return string
     */
    protected function normalizeString(string $string): string
    {
        // Convert to uppercase and remove accents using iconv if available
        if (function_exists('iconv')) {
            return strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT', $string));
        }
        
        // Fallback: simple accent removal
        $normalized = strtoupper($string);
        $normalized = str_replace(
            ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Þ', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'þ', 'ÿ'],
            ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'TH', 'SS', 'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'TH', 'Y'],
            $normalized
        );
        return $normalized;
    }
}
