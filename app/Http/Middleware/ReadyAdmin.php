<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class ReadyAdmin { public function handle(Request $r, Closure $next) { if ($r->user()->role !== 'admin' || $r->user()->must_change_password) return response()->json(['message'=>'Akses ditolak atau password harus diganti.'],403); return $next($r); } }
