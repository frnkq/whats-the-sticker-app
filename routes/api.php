<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\HandleMessage;

Route::get('/', function(){
    return response()->json('Hi', 200);
});


Route::prefix('whatsapp')->group(function () {

    Route::get('/webhook', function (Request $request) {
        if ($request->query('hub_mode') === 'subscribe' && 
            $request->query('hub_verify_token') === 'HAPPY') {
            return response($request->query('hub_challenge'));
        }
        return response()->json([], 400);
    });
    
    Route::post('/webhook', HandleMessage::class);
    
});

