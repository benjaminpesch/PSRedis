<?php

namespace PSRedis\Client\Adapter\Phpredis;

/**
 * Interface PredisClientFactory
 *
 * Creates the actual clients to talk to Redis and Sentinel nodes with.  This allows us to mock the objects created
 * in order to unit test the library
 *
 * @package PSRedis\Client\Adapter\Phpredis
 */
interface PhpredisClientFactory
{
    public function createClient($clientType, array $parameters = array());
} 