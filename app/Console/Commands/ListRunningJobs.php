<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Horizon\Repositories\RedisJobRepository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\RedisManager;

class ListRunningJobs extends Command
{
    protected $signature = 'horizon:list-running-jobs';
    protected $description = 'List jobs currently running on the host';

    public function handle()
    {
	 // Get the Redis connection using RedisFacade, which returns the correct Redis instance
    	Redis::connection();
        $jobRepository = new RedisJobRepository(Redis::getFacadeRoot());

        // Get all jobs reserved by workers
        $runningJobs = Redis::keys('laravel_horizon:job:*');
	\Log::info('log starting....');
	\Log::info($runningJobs);
        $data = [];
        foreach ($runningJobs as $key) {
            $job = Redis::hgetall($key);
		\Log::info($job);
            if (isset($job['reserved_at']) && !isset($job['completed_at']) && !isset($job['failed_at'])) {
                $data[] = [
                    'JOB_ID' => $job['id'] ?? 'Unknown',
                    'JOB_CLASS' => $job['displayName'] ?? 'Unknown',
                    'QUEUE_NAME' => $job['queue'] ?? 'Unknown',
                    'START_TIME' => isset($job['reserved_at']) ? date('Y-m-d H:i:s', $job['reserved_at']) : 'Unknown',
                ];
            }
	}
	\Log::info('log ending......');
	

        // Output the result
        $this->table(['JOB_ID', 'JOB_CLASS', 'QUEUE_NAME', 'START_TIME'], $data);
    }
}
