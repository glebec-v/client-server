<?php

namespace GlebecV\Network;

use GlebecV\Exceptions\ClientConnectionException;

class Client
{
    private const CONNECTION = [
        'address' => '127.0.0.1',
        'port'    => '9990',
    ];

    private $connection;
    private $length;

    /**
     * Client constructor.
     * @param int $chunkLength
     * @param array $connection
     */
    public function __construct(int $chunkLength = null, array $connection = [])
    {
        $this->connection['address'] = $connection['address'] ?? self::CONNECTION['address'];
        $this->connection['port']    = $connection['port']    ?? self::CONNECTION['port'];
        $this->length = $chunkLength ?? 2048;
    }

    /**
     * @param $message
     * @return string
     * @throws ClientConnectionException
     */
    public function send($message): string
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (false === $socket) {
            throw new ClientConnectionException("Socket create failed: ".socket_strerror(socket_last_error()), 1);
        }
        if (false === socket_connect($socket, $this->connection['address'], $this->connection['port'])) {
            throw new ClientConnectionException("Socket connect failed: ".socket_strerror(socket_last_error()), 1);
        }

        socket_write($socket, $message, strlen($message));
        $response = '';
        while ('' !== ($chunk = socket_read($socket, $this->length))) {
            $response .= $chunk;
        }
        socket_close($socket);

        return $response;
    }
}