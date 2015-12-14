<?php


namespace PSRedis\Client\Adapter\Phpredis;


use PSRedis\Client;
use PSRedis\Exception\ConfigurationError;

/**
 * Class PredisClientCreator
 *
 * Factory to create the Predis clients that allow us to talk to Redis and Sentinel nodes.
 *
 * @package PSRedis\Client\Adapter\Phpredis
 */
class PhpredisClientCreator
    implements PhpredisClientFactory
{
    public function createClient($clientType, array $parameters = array())
    {
        switch($clientType)
        {
            case Client::TYPE_REDIS:
                return $this->createRedisClient($parameters);
            case Client::TYPE_SENTINEL:
                return $this->createSentinelClient($parameters);
        }

        throw new ConfigurationError('To create a client, you need to provide a valid client type');
    }

    private function createSentinelClient(array $parameters = array())
    {
        $predisClient = new \Predis\Client($parameters);
        $predisClient->getProfile()->defineCommand(
            'sentinel', '\\PSRedis\\Client\\Adapter\\Predis\\Command\\SentinelCommand'
        );
        $predisClient->getProfile()->defineCommand(
            'role', '\\PSRedis\\Client\\Adapter\\Predis\\Command\\RoleCommand'
        );
        $predisClient->connect();
        return $predisClient;
    }

    private function createRedisClient(array $parameters = array())
    {
        $phpredisClient = new \Redis();
        $phpredisClient->connect($parameters["host"], $parameters["port"]);
        return $phpredisClient;
    }
} 