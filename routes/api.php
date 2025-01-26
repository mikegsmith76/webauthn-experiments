<?php

use Illuminate\Support\Facades\Route;

Route::get("/v1/register/init", \App\Http\Controllers\Api\V1\Register\Init::class);
Route::post("/v1/register/verify", \App\Http\Controllers\Api\V1\Register\Verify::class);

Route::get("/v2/register/init", \App\Http\Controllers\Api\V2\Register\Init::class);
Route::post("/v2/register/verify", \App\Http\Controllers\Api\V2\Register\Verify::class);