<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Artisan;

class ListRunningJobs extends Command
{
    protected $signature = 'horizon:list-running-jobs';
    protected $description = 'List jobs currently running on the host';

    public function handle()
    {
        $supervisor_id = gethostname();
        if (!$this->isHorizonRunning()) {
            $this->warn('Horizon is not running. No jobs will be listed.');
            return;
        }
        // Get all reserved queue keys from Redis
        $key = 'queues:default:reserved';

        $runningJobs = [];
        $currentTimestamp = time(); // Current timestamp in seconds

        // Fetch all jobs with scores (timestamps) for each queue
        $reservedJobs = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);

        // Loop through each reserved job in the current queue
        foreach ($reservedJobs as $jobData => $timestamp) {
            // Decode the JSON data for each job
            $jobDetails = json_decode($jobData, true);

            $string =$jobDetails['data']['command'];
            $unserialized = unserialize($string);
            $supervisor_id_redis = $unserialized->supervisor_id;

            // Check if the job belongs to one of the current host's supervisors
            if ($jobDetails && $supervisor_id_redis==$supervisor_id) {
                $timeout = $jobDetails['timeout'] ?? null;

                if ($timeout === null || $currentTimestamp <= $timestamp + $timeout) {
                    $runningJobs[] = [
                        'JOB_ID' => $jobDetails['uuid'] ?? 'Unknown',
                        'JOB_CLASS' => $jobDetails['displayName'] ?? 'Unknown',
                        'QUEUE_NAME' => str_replace('queues:', '', explode(':', $key)[1]) ?? 'Unknown',
                        'START_TIME' => date('Y-m-d H:i:s', $timestamp),
                        'ATTEMPTS' => $jobDetails['attempts'] ?? 0,
                    ];
                }
            }
        }

        // Output the result in a table format
        $this->table(['JOB_ID', 'JOB_CLASS', 'QUEUE_NAME', 'START_TIME', 'ATTEMPTS'], $runningJobs);
    }

    private function isHorizonRunning()
    {
        $horizonStatus = Artisan::call('horizon:status');
        $output = trim(Artisan::output());

        if (str_contains($output, 'Horizon is running')) {
            return true; // Horizon is running
        }

        return false; // Horizon is not running

    }
}
