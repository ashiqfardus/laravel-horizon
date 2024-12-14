<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ListRunningJobs extends Command
{
    protected $signature = 'horizon:list-running-jobs';
    protected $description = 'List jobs currently running on the host';

    public function handle()
    {
	// Get all reserved queue keys from Redis
    $queueKeys = Redis::keys('queues:*:reserved'); // Match all reserved queues

    $runningJobs = [];
    $currentTimestamp = time(); // Current timestamp in seconds

    // Loop through all reserved queue keys
    foreach ($queueKeys as $key) {
        // Fetch all jobs with scores (timestamps) for each queue
        $reservedJobs = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);

        // Loop through each reserved job in the current queue
        foreach ($reservedJobs as $jobData => $timestamp) {
            // Decode the JSON data for each job
            $jobDetails = json_decode($jobData, true);

            if ($jobDetails) {
                // Determine if the job is still running
                $timeout = $jobDetails['timeout'] ?? null; // Get job's timeout (null if not set)

                // Check if the job is still running
                if ($timeout === null || $currentTimestamp <= $timestamp + $timeout) {
                    $runningJobs[] = [
                        'JOB_ID' => $jobDetails['uuid'] ?? 'Unknown',
                        'JOB_CLASS' => $jobDetails['displayName'] ?? 'Unknown',
                        'QUEUE_NAME' => str_replace('queues:', '', explode(':', $key)[1]) ?? 'Unknown', // Extract queue name
                        'START_TIME' => date('Y-m-d H:i:s', $timestamp),
                        'ATTEMPTS' => $jobDetails['attempts'] ?? 0,
                    ];
                }
            }
        }
    }

    // Output the result in a table format
    $this->table(['JOB_ID', 'JOB_CLASS', 'QUEUE_NAME', 'START_TIME', 'ATTEMPTS'], $runningJobs);
    }
}
