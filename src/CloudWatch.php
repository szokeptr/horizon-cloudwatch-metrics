<?php

namespace HorizonCW;


use Aws\CloudWatch\CloudWatchClient;

trait CloudWatch
{
    /** @var CloudWatchClient */
    protected $client;

    public function getClient()
    {
        if ($this->client !== null) {
            return $this->client;
        }

        return $this->client = new CloudWatchClient([
            'credentials' => config('horizon-cw.credentials'),
            'region' => config('horizon-cw.region'),
            'version' => '2010-08-01',
        ]);
    }
}
