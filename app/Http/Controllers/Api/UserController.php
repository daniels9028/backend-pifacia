<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Models\Audit;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    public function show($id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    public function audit()
    {
        $audits = Audit::with(['user', 'auditable']) // include polymorphic relasi
            ->latest()
            ->get()
            ->map(function ($audit) {
                $model = optional($audit->auditable);

                return [
                    'user' => optional($audit->user)->name ?? 'System',
                    'event' => $audit->event,
                    'model_type' => class_basename($audit->auditable_type),
                    'id' => $audit->auditable_id,
                    'model_title' => $model->title ?? $model->name ?? '[deleted]', // fallback properties
                    'model_summary' => method_exists($model, 'getAuditLabel')
                        ? $model->getAuditLabel()
                        : '-', // if you add a helper on model
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'created_at' => $audit->created_at->toDateTimeString(),
                ];
            });

        return response()->json(['data' => $audits]);
    }
}
