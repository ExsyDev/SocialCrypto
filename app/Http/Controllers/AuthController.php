<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class AuthController extends Controller
{

    /**
     * Redirect the user to the Provider authentication page.
     *
     * @param string $provider
     * @return JsonResponse
     */
    public function redirectToProvider(string $provider): JsonResponse
    {
        // Validate the provider
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }

        // Redirect user to the Provider authentication page
        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * Obtain the user information from Provider callback.
     *
     * @param string $provider
     * @return JsonResponse
     */
    public function handleProviderCallback(string $provider): JsonResponse
    {
        // Validate the provider
        $validated = $this->validateProvider($provider);

        if (!is_null($validated)) {
            return $validated;
        }

        try {
            // Get user information from Provider
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (ClientException $exception) {
            // Handle invalid credentials
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }

        $userCreated = User::firstOrCreate(
            ['email' => $user->getEmail()],
            [
                'email_verified_at' => now(),
                'name' => $user->getName(),
                'status' => true,
            ]
        );

        // Update user provider information
        $userCreated->providers()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $user->getId(),
            ],
            ['avatar' => $user->getAvatar()]
        );

        // Generate access token
        $token = $userCreated->createToken('api-token')->plainTextToken;

        // Return user information with access token
        return response()->json($userCreated, 200, ['Access-Token' => $token]);
    }

    /**
     * Validate the provider.
     *
     * @param string $provider
     * @return JsonResponse|null
     */
    protected function validateProvider(string $provider): ?JsonResponse
    {
        if (!in_array($provider, ['telegram', 'google'])) {
            return response()->json(['error' => 'Please login using telegram or google'], 422);
        }
        return null;
    }
}
