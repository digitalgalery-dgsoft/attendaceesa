<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
            'device_id' => 'nullable|string',
            'device_name' => 'nullable|string',
            'fcm_token' => 'nullable|string',
        ]);

        $loginId = trim($request->email);
        $password = $request->password;

        // Cari record karyawan yang berstatus AKTIF (is_active = true) berdasarkan email atau NIK
        $employee = Employee::where(function($query) use ($loginId) {
                $query->where('email', $loginId)
                      ->orWhere('employee_no', $loginId);
            })
            ->where('is_active', true)
            ->with(['company', 'principal', 'branch', 'department', 'position', 'user'])
            ->orderByDesc('id')
            ->first();

        if (!$employee) {
            // Cek apakah karyawan terdaftar di peer server (AKP / ATK) melalui Smart Gateway Relay
            $relayResponse = \App\Services\SmartGatewayRelayService::attemptRelayLogin($request);
            if ($relayResponse) {
                return $relayResponse;
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Email/NIK atau password salah.'
            ], 401);
        }

        // Verifikasi password pada data employee aktif
        $passwordValid = false;
        if (!empty($employee->password) && Hash::check($password, $employee->password)) {
            $passwordValid = true;
        } elseif ($employee->user && !empty($employee->user->password) && Hash::check($password, $employee->user->password)) {
            $passwordValid = true;
        }

        if (!$passwordValid) {
            // Jika password gagal di lokal, coba juga di peer server
            $relayResponse = \App\Services\SmartGatewayRelayService::attemptRelayLogin($request);
            if ($relayResponse) {
                return $relayResponse;
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Email/NIK atau password salah.'
            ], 401);
        }

        if ($request->filled('device_id')) {
            if (empty($employee->device_id)) {
                $employee->device_id = $request->device_id;
                $employee->device_name = $request->device_name;
                $employee->save();
            } else if ($employee->device_id !== $request->device_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun ini sudah ditautkan dengan perangkat lain. Silakan hubungi admin untuk melakukan Reset Device.'
                ], 401);
            }
        }

        if ($request->filled('fcm_token')) {
            $employee->fcm_token = $request->fcm_token;
            $employee->save();
        }

        // Hapus token lama jika ada (opsional)
        $employee->tokens()->delete();

        $token = $employee->createToken('mobile_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil',
            'data'    => [
                'access_token'  => $token,
                'token_type'    => 'Bearer',
                'employee_data' => $employee,
            ]
        ]);
    }

    public function me(Request $request)
    {
        $employee = $request->user();
        $employee->load(['company', 'principal', 'branch', 'department', 'position']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'employee_data' => $employee,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            \Illuminate\Support\Facades\Cache::forget('gateway_relay_token_' . $bearerToken);
        }

        if ($request->user() && method_exists($request->user(), 'currentAccessToken') && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $employee = $request->user();

        $request->validate([
            'language' => 'nullable|string',
            'timezone' => 'nullable|string',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Update password if provided
        if ($request->filled('current_password') && $request->filled('password')) {
            if (!Hash::check($request->current_password, $employee->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Password saat ini tidak sesuai.'
                ], 400);
            }
            $employee->password = Hash::make($request->password);
        }

        if ($request->has('language')) {
            $employee->language = $request->language;
        }

        if ($request->has('timezone')) {
            $employee->timezone = $request->timezone;
        }

        if ($request->hasFile('photo')) {
            // Delete old photo if it exists and not default
            if ($employee->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($employee->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->photo);
            }
            $path = $request->file('photo')->store('employees', 'public');
            $employee->photo = $path;
        }

        $employee->save();
        $employee->load(['company', 'principal', 'branch', 'department', 'position']);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'employee_data' => $employee,
            ]
        ]);
    }
}
