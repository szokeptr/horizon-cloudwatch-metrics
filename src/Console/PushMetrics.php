<?php

namespace HorizonCW\Console;

use Aws\Exception\AwsException;
use HorizonCW\CloudWatch;
use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\MetricsRepository;

class PushMetrics extends Command
{
    use CloudWatch;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon-cw:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store a snapshot of the queue metrics';

    protected $jobMetrics = [
        'throughput',
        'runtime',
    ];

    protected $queueMetrics = [
        'throughput',
        'runtime',
    ];

    protected $metricUnits = [
        'throughput' => 'Count',
        'runtime' => 'Milliseconds',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        /** @var MetricsRepository $metrics */
        $metrics = app(MetricsRepository::class);

        $jobs = $metrics->measuredJobs();

        foreach ($jobs as $job) {
            $snapshots = $metrics->snapshotsForJob($job);
            foreach ($this->jobMetrics as $metric) {
                if (count($snapshots) === 0) {
                    continue;
                }

                $snapshots = collect($snapshots)->sortByDesc('time')->take(20)->values()->all();

                $metricData = $this->mapMetricData($snapshots, $metric, [
                    'Name' => 'Job',
                    'Value' => $job
                ]);

                $this->pushMetric($metricData);

                $this->info("$metric metrics for $job were pushed to CloudWatch");
            }
        }

        $queues = $metrics->measuredQueues();

        foreach ($queues as $queue) {
            $snapshots = $metrics->snapshotsForQueue($queue);

            foreach ($this->queueMetrics as $metric) {
                if (count($snapshots) === 0) {
                    continue;
                }

                $snapshots = collect($snapshots)->sortByDesc('time')->take(20)->values()->all();

                $metricData = $this->mapMetricData($snapshots, $metric, [
                    'Name' => 'Queue',
                    'Value' => $queue,
                ]);

                $this->pushMetric($metricData);

                $this->info("$metric metrics for $queue were pushed to CloudWatch");
            }
        }
    }

    protected function mapMetricData($data, $metric, ...$dimensions)
    {
        return array_map(function($item) use ($metric, $dimensions) {
            $value = $item->{$metric};

            return [
                'MetricName' => $metric,
                'Timestamp' => $item->time,
                'Value' => (float) $value,
                'Unit' => $this->metricUnits[$metric],
                'Dimensions' => array_merge($dimensions, [
                    [
                        'Name' => 'Environment',
                        'Value' => config('app.env'),
                    ]
                ])
            ];

        }, $data);
    }

    protected function pushMetric($data)
    {
        try {
            $result = $this->getClient()->putMetricData([
                'Namespace' => config('horizon-cw.namespace'),
                'MetricData' => $data
            ]);
        } catch (\Exception $e) {
            $this->error("Failed to push metrics: " . $e->getMessage());
            return;
        }

        return $result;
    }
}
