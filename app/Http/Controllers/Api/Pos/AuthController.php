<?php

namespace App\Http\Controllers\Api\Pos;

use App\Models\User;
use App\Services\PosShiftService;
use App\Support\Api\PosApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (! $user->canAccessAdmin()) {
            throw ValidationException::withMessages([
                'email' => ['Akun tidak memiliki akses admin.'],
            ]);
        }

        if (! $user->hasPermission('pos.access')) {
            throw ValidationException::withMessages([
                'email' => ['Akun tidak memiliki akses POS.'],
            ]);
        }

        $token = $user->createToken(
            $credentials['device_name'] ?? 'pos-terminal',
            ['pos'],
        )->plainTextToken;

        return PosApiResponse::success($this->userPayload($user, $token));
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return PosApiResponse::success(['loggedOut' => true]);
    }

    public function me(Request $request, PosShiftService $shiftService)
    {
        return PosApiResponse::success(
            $this->userPayload($this->posUser($request), null, $shiftService),
        );
    }

    public function update(Request $request, PosShiftService $shiftService)
    {
        $user = $this->posUser($request);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (isset($validated['password'])) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password lama tidak sesuai.'],
                ]);
            }
            $user->password = $validated['password'];
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        $user->save();

        return PosApiResponse::success(
            $this->userPayload($user->fresh(), null, $shiftService),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, ?string $token = null, ?PosShiftService $shiftService = null): array
    {
        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'isSuperAdmin' => $user->isSuperAdmin(),
                'permissions' => $user->permissionList(),
            ],
            'currentShift' => $shiftService
                ? $shiftService->serializeShift($shiftService->currentOpenShift($user))
                : null,
        ];

        if ($token !== null) {
            $payload['token'] = $token;
        }

        return $payload;
    }
}
