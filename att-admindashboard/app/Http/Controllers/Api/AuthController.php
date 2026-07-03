<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kredensial tidak valid'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Cek apakah user adalah karyawan
        $employee = \App\Models\Employee::where('user_id', $user->id)
            ->with(['company', 'branch'])
            ->first();
            
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'employee_data' => $employee
            ]
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $employee = \App\Models\Employee::where('user_id', $user->id)
            ->with(['company', 'branch'])
            ->first();
            
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'employee_data' => $employee
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }
}
