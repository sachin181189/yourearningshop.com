<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class Checkauth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $users = DB::table('users')->select('id')
        ->where('id', $request->user_id)
        ->where('auth_key', $request->header('auth_key'))
        ->first();
        if(!$users){
            return response()->json(['msg' => 'Invalid auth key','status'=>false], 401);
        }
        return $next($request);
    }
}
