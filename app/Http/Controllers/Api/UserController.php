<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        try {
           
            \Log::debug('Attempting to fetch users list');
            
            $users = User::select('id', 'name', 'email', 'created_at')
                ->paginate(10);

            \Log::debug('Successfully fetched users', ['count' => $users->count()]);
            
            return response()->json($users);

        } catch (\Exception $e) {
            \Log::error('UserController index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error listing users',
                'details' => config('app.debug') ? $e->getMessage() : []
            ], 500);
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $validated = $request->validated();
            
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at
            ], 201);

        } catch (\Exception $e) {
            \Log::error('User creation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'User creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::select('id', 'name', 'email', 'created_at')
                    ->find($id); // Use find() instead of findOrFail()

            if (!$user) {
                return response()->json([
                    'error' => 'User not found'
                ], 404);
            }

            return response()->json($user);

        } catch (\Exception $e) {
            \Log::error("UserController show error: " . $e->getMessage());
            return response()->json([
                'error' => 'Server error'
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        try {
            $user = User::find($id); // Changed from findOrFail to find
            
            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }

            $data = $request->validated();

            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']); // Using Laravel's bcrypt
            }

            $user->update($data);
            Cache::forget("user:{$id}");
            Cache::forget("users:list:*");

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'updated_at' => $user->updated_at
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation error.', 422, $e->errors());
        } catch (Exception $e) {
            Log::error("User update failed - ID: {$id}", [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            return $this->errorResponse('Error updating user.', 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $user = User::find($id); // Changed from findOrFail to find
            
            if (!$user) {
                return $this->errorResponse('User not found.', 404); // Consistent English messages
            }

            $user->delete();

            Cache::forget("user:{$id}");
            Cache::forget('users:list:*');

            return response()->json([
                'message' => 'User deleted successfully',
                'deleted_at' => now()->toDateTimeString()
            ]);

        } catch (Exception $e) {
            Log::error("User deletion failed - ID: {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Error deleting user.', 500);
        }
    }

    protected function errorResponse(string $message, int $statusCode = 400, $details = null)
    {
        $response = ['error' => $message];

        if ($details) {
            $response['details'] = is_string($details) ? $details : $details;
        }

        return response()->json($response, $statusCode);
    }
}
