<?php

namespace Etlok\Crux\WebSockets\Channels;

use Etlok\Crux\WebSockets\Contracts\ChannelManager;
use Etlok\Crux\WebSockets\Exceptions\InvalidSignature;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use React\Promise\PromiseInterface;
use stdClass;

class Channel
{
    /**
     * The channel name.
     *
     * @var string
     */
    protected $name;

    /**
     * The connections that got subscribed to this channel.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * Create a new instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->channelManager = app(ChannelManager::class);
    }

    /**
     * Get channel name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the list of subscribed connections.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Check if the channel has connections.
     *
     * @return bool
     */
    public function hasConnections(): bool
    {
        return count($this->getConnections()) > 0;
    }

    /**
     * Add a new connection to the channel.
     *
     * @see    https://pusher.com/docs/pusher_protocol#presence-channel-events
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return bool
     */
    public function subscribe(ConnectionInterface $connection, stdClass $payload): bool
    {
        $this->saveConnection($connection);

        $connection->send(json_encode([
            'event' => 'internal:subscription_succeeded',
            'channel' => $this->getName(),
        ]));

        return true;
    }

    /**
     * Unsubscribe connection from the channel.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return PromiseInterface
     */
    public function unsubscribe(ConnectionInterface $connection): bool
    {
        if (! $this->hasConnection($connection)) {
            return false;
        }

        unset($this->connections[$connection->socketId]);
        return true;
    }

    /**
     * Check if the given connection exists.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return bool
     */
    public function hasConnection(ConnectionInterface $connection): bool
    {
        return isset($this->connections[$connection->socketId]);
    }

    /**
     * Store the connection to the subscribers list.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return void
     */
    public function saveConnection(ConnectionInterface $connection)
    {
        $this->connections[$connection->socketId] = $connection;
    }

    /**
     * Broadcast a payload to the subscribed connections.
     *
     * @param  string|int  $appId
     * @param  \stdClass  $payload
     * @param  bool  $replicate
     * @return bool
     */
    public function broadcast($appId, stdClass $payload, bool $replicate = true): bool
    {
        collect($this->getConnections())
            ->each->send(json_encode($payload));

        if ($replicate) {
            $this->channelManager->broadcastAcrossServers($appId, null, $this->getName(), $payload);
        }

        return true;
    }

    /**
     * Broadcast a payload to the locally-subscribed connections.
     *
     * @param  string|int  $appId
     * @param  \stdClass  $payload
     * @return bool
     */
    public function broadcastLocally($appId, stdClass $payload): bool
    {
        return $this->broadcast($appId, $payload, false);
    }

    /**
     * Broadcast the payload, but exclude a specific socket id.
     *
     * @param  \stdClass  $payload
     * @param  string|null  $socketId
     * @param  string|int  $appId
     * @param  bool  $replicate
     * @return bool
     */
    public function broadcastToEveryoneExcept(stdClass $payload, ?string $socketId, $appId, bool $replicate = true)
    {
        if ($replicate) {
            $this->channelManager->broadcastAcrossServers($appId, $socketId, $this->getName(), $payload);
        }

        if (is_null($socketId)) {
            return $this->broadcast($appId, $payload, $replicate);
        }

        collect($this->getConnections())->each(function (ConnectionInterface $connection) use ($socketId, $payload) {
            if ($connection->socketId !== $socketId) {
                $connection->send(json_encode($payload));
            }
        });

        return true;
    }

    /**
     * Broadcast the payload, but exclude a specific socket id.
     *
     * @param  \stdClass  $payload
     * @param  string|null  $socketId
     * @param  string|int  $appId
     * @return bool
     */
    public function broadcastLocallyToEveryoneExcept(stdClass $payload, ?string $socketId, $appId)
    {
        return $this->broadcastToEveryoneExcept(
            $payload, $socketId, $appId, false
        );
    }

    /**
     * Check if the signature for the payload is valid.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @param  \stdClass  $payload
     * @return void
     *
     * @throws InvalidSignature
     */
    protected function verifySignature(ConnectionInterface $connection, stdClass $payload)
    {
        $signature = "{$connection->socketId}:{$this->getName()}";

        if (isset($payload->channel_data)) {
            $signature .= ":{$payload->channel_data}";
        }

        if (! hash_equals(
            hash_hmac('sha256', $signature, $connection->app->secret),
            Str::after($payload->auth, ':'))
        ) {
            throw new InvalidSignature;
        }
    }
}