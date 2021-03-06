<?php

namespace PSRedis\Client\Adapter;

use Predis\Connection\ConnectionException;
use PSRedis\Client\Adapter\Predis\Command\SentinelCommand;
use PSRedis\Client\Adapter\Phpredis\PhpredisClientFactory;
use PSRedis\Client\ClientAdapter;
use PSRedis\Client;
use PSRedis\Exception\ConnectionError;
use PSRedis\Exception\SentinelError;

/**
 * Class PredisClientAdapter
 *
 * Adapts the PSRedis\Client objects to Predis
 * @link
 *
 * @package PSRedis\Client\Adapter
 */
class PhpredisClientAdapter
    extends AbstractClientAdapter
    implements ClientAdapter
{
    /**
     * The Predis client to use when sending commands to the redis server
     * @var \Predis\Client
     */
    private $redisClient;

    /**
     * Factory allows us to mock the creation of the actual redis clients
     * @var \PSRedis\Client\Adapter\Predis\PredisClientFactory
     */
    private $phpredisClientFactory;

    /**
     * @var string
     */
    private $clientType;

    /**
     * @param PhpredisClientFactory $clientFactory
     * @param $clientType string
     */
    public function __construct(PhpredisClientFactory $clientFactory, $clientType)
    {
        $this->phpredisClientFactory = $clientFactory;
        $this->clientType = $clientType;
    }

    /**
     * @return \Predis\Client
     */
    private function getPhpredisClient()
    {
        if (empty($this->redisClient)) {
            $this->connect();
        }

        return $this->redisClient;
    }

    /**
     * Creates a connect to Redis or Sentinel using the Predis\Client object.  It proxies the connecting and converts
     * specific client exceptions to more generic adapted ones in PSRedis
     *
     * @throws \PSRedis\Exception\ConnectionError
     */
    public function connect()
    {
        try {
            $this->redisClient = $this->phpredisClientFactory->createClient($this->clientType, $this->getRedisClientParameters());
            $this->isConnected = $this->redisClient->isConnected();
        } catch (ConnectionException $e) {
            throw new ConnectionError($e->getMessage());
        }
    }

    private function getRedisClientParameters()
    {
        return array(
            'scheme'    => 'tcp',
            'host'      => $this->ipAddress,
            'port'      => $this->port,
        );
    }

    /**
     * Gets the master node information from a sentinel.  This will still attempt to execute the sentinel command if
     * executed on a redis client, but it will not recognize the command when attempted.
     *
     * @param $nameOfNodeSet
     * @return Client
     * @throws \PSRedis\Exception\SentinelError
     */
    public function getMaster($nameOfNodeSet)
    {
        list($masterIpAddress, $masterPort) = $this->getPhpredisClient()->sentinel(SentinelCommand::GETMASTER, $nameOfNodeSet);

        if (!empty($masterIpAddress) AND !empty($masterPort)) {
            return new \PSRedis\Client($masterIpAddress, $masterPort, new PhpredisClientAdapter($this->phpredisClientFactory, Client::TYPE_REDIS));
        }

        throw new SentinelError('The sentinel does not know the master address');
    }

    /**
     * Inspects the role of the node we are currently connected to
     *
     * @see http://redis.io/commands/role
     * @return mixed
     */
    public function getRole()
    {
        $info = $this->getPhpredisClient()->info();        
        $role = array();
        $role[] = $info['role'];
        if (isset($info['role'])) {
            $role[] = $info['role'];
        } else {
            $role[] = $info['redis_mode'];
        }
        return $role;
        //return $this->getPhpredisClient()->role();
    }

    /**
     * @param $methodName
     * @param array $methodParameters
     * @return mixed|void
     */
    public function __call($methodName, array $methodParameters = array())
    {
        try {
            return call_user_func_array(array($this->getPhpredisClient(), $methodName), $methodParameters);
        } catch (ConnectionException $e) {
            throw new ConnectionError($e->getMessage());
        }

    }
}