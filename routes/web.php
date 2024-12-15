<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use App\Jobs\DummyJob;
use App\Http\Controllers\getRunningJobController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('check-host', function (){
   return gethostname();
});

Route::get('/dispatch-job', function () {
	DummyJob::dispatch()->delay(now()->addMinutes(30));
	DummyJob::dispatch();
	return 'Job dispatched!';
});

Route::get('/running-jobs', [getRunningJobController::class, 'index']);

Route::get('test-redis', function () {
	try {

            Redis::set('key', 'value');
            return Redis::get('');

    } catch (\Exception $e) {
        return 'Redis connection error: ' . $e->getMessage();
    }
});
