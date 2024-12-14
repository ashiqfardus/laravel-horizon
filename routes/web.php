<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use App\Jobs\DummyJob;
use Laravel\Horizon\Repositories\RedisJobRepository;


Route::get('/', function () {
    return view('welcome');
});



Route::get('/dispatch-job', function () {
	DummyJob::dispatch()->delay(now()->addMinutes(30));
	DummyJob::dispatch();
	return 'Job dispatched!';
});

Route::get('/running-jobs', function () {
	
	$keys = Redis::keys('queues:*:reserved');

	dd($keys);
	$key = 'queues:default:reserved';

// Fetch all jobs with scores (timestamps)
$reservedJobs = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);

$runningJobs = [];
$currentTimestamp = time(); // Current timestamp in seconds

foreach ($reservedJobs as $jobData => $timestamp) {
    // Decode the JSON data for each job
    $jobDetails = json_decode($jobData, true);

    if ($jobDetails) {
        // Determine if the job is still running
        $timeout = $jobDetails['timeout'] ?? null; // Get job's timeout (null if not set)

        if ($timeout === null || $currentTimestamp <= $timestamp + $timeout) {
            $runningJobs[] = [
                'uuid' => $jobDetails['uuid'] ?? null,
                'displayName' => $jobDetails['displayName'] ?? 'Unknown',
                'attempts' => $jobDetails['attempts'] ?? 0,
                'pushedAt' => $jobDetails['pushedAt'] ?? null,
                'reservedAt' => $timestamp,
                'isRunning' => true,
            ];
        }
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
