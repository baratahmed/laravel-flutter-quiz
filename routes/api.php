<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::post('auth', [ApiController::class, 'authenticate']);
Route::post('register', [ApiController::class, 'register']);
Route::post('logout', [ApiController::class, 'logout']);

Route::post('fcm/token', [ApiController::class, 'saveFCMToken']);

Route::group(['middleware'=>['auth.jwt']], function(){

     Route::get('qwerty', [ApiController::class, 'qwerty']);

    // Route::get('/test', function(){
    //     return "Hello World!!!";
    // });   
    
    Route::get('me', [ApiController::class, 'getAuthenticatedUser']);
    Route::get('dashboard', [ApiController::class, 'dashboard']);
    Route::get('quiz/start/{id}', [ApiController::class, 'startQuiz']);
    Route::get('quiz/myquizzes', [ApiController::class, 'myQuizzes']);
    Route::post('quiz/attempt/{qid}/', [ApiController::class, 'attempt']);
    Route::get('quiz/{qid}/attempts', [ApiController::class, 'getAttempts']);
    Route::post('settings/changepassword', [ApiController::class, 'changePassword']);
    Route::get('quiz/full/{qid}', [ApiController::class, 'fullQuiz']);
    Route::get('quiz/{id}/seen', [ApiController::class, 'seenResults']);



});