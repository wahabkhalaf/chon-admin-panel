<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and is admin
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Please login'
            ], 401);
        }

        // Check if user has admin role (adjust this based on your user system)
        $user = auth()->user();
        
        // Option 1: Check for admin role in users table
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied - Admin privileges required'
            ], 403);
        }

        // Option 2: If you're using Spatie Laravel Permission
        // if (!$user->hasRole('admin')) {
        //     $this->message = 'Access denied - Admin privileges required';
        //     return $this->error(403);
        // }

        return $next($request);
    }
}
