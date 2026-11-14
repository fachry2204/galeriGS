<?php
use Illuminate\Support\Facades\Route;

Route::get('/{path?}', fn () => view('gallery'))
    ->where('path', '^(?!api(?:/|$)|storage(?:/|$)).*');
