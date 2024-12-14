<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use App\Jobs\DummyJob;
use Laravel\Horizon\Repositories\RedisJobRepository;


Route::get('/', function () {
    return view('welcome');
});



Route::get('/dispatch-job', function () {
	DummyJob::dispatch();

	return 'Job dispatched!';
});

Route::get('/failed-jobs', function () {
	// Define the key for the default queue's reserved jobs
    $key = 'queues:default:reserved';

    // Fetch all jobs with scores (timestamps)
    $reservedJobs = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);

    $runningJobs = [];

    foreach ($reservedJobs as $jobData => $timestamp) {
        // Decode the JSON data for each job
        $jobDetails = json_decode($jobData, true);

        if ($jobDetails) {
            $runningJobs[] = [
                'uuid' => $jobDetails['uuid'] ?? null,
                'displayName' => $jobDetails['displayName'] ?? 'Unknown',
                'attempts' => $jobDetails['attempts'] ?? 0,
                'pushedAt' => $jobDetails['pushedAt'] ?? null,
                'reservedAt' => $timestamp,
            ];
        }
    }

    // Return or print the list of currently running jobs
    return $runningJobs;
	
});

Route::get('test-redis', function () {
	try {

            Redis::set('key', 'value');
            return Redis::get('');

    } catch (\Exception $e) {
        return 'Redis connection error: ' . $e->getMessage();
    }
});
