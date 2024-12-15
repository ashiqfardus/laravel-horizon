<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;

class getRunningJobController extends Controller
{
    public function index()
    {
        $hostname = gethostname();

//        if (!$this->isHorizonRunning()) {
//            $this->warn('Horizon is not running. No jobs will be listed.');
//            return;
//        }
        // Get all reserved queue keys from Redis
        $queueKeys = Redis::keys('queues:default:reserved'); // Match all reserved queues

        $runningJobs = [];
        $currentTimestamp = time(); // Current timestamp in seconds

        foreach ($queueKeys as $key) {
            // Fetch all jobs with scores (timestamps) for each queue
            $reservedJobs = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);

            // Loop through each reserved job in the current queue
            foreach ($reservedJobs as $jobData => $timestamp) {
                // Decode the JSON data for each job
                $jobDetails = json_decode($jobData, true);
                $string =$jobDetails['data']['command'];
                $unserialized = unserialize($string);
                $supervisor_id = $unserialized->supervisor_id;

                // Check if the job belongs to one of the current host's supervisors
                if ($jobDetails && $supervisor_id==$hostname) {
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
        }
        return $runningJobs;

        // Output the result in a table format
        $this->table(['JOB_ID', 'JOB_CLASS', 'QUEUE_NAME', 'START_TIME', 'ATTEMPTS'], $runningJobs);
    }
}
