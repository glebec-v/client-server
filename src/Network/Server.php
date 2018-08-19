<?php

namespace GlebecV\Network;

use GlebecV\BracketsCounter;
use GlebecV\Exceptions\ServerConnectionException;

class Server
{
    private const PARAMS = [
        'address' => '127.0.0.1',
        'port'    => '9990',
        'threads' => 5
    ];

    private $params;
    private $connection;
    private $executor;

    public function __construct(string $executorClass, array $params = [])
    {
        $this->executor = $executorClass;
        $this->params['address'] = $params['address'] ?? self::PARAMS['address'];
        $this->params['port']    = $params['port']    ?? self::PARAMS['port'];
        $this->params['threads'] = $params['threads'] ?? self::PARAMS['threads'];
        $this->connection = $this->connect($this->params['address'], $this->params['port']);
    }

    public function run()
    {
        for ($i = 0; $i < $this->params['threads']; $i++) {
            $this->fork();
        }
        while (-1 !== ($cid = pcntl_waitpid(0, $status) )) {
            $exitCode = pcntl_wexitstatus($status);
            echo "Child process {$cid} exited with code {$exitCode}".PHP_EOL;
        }
    }

    public function __destruct()
    {
        socket_close($this->connection);
    }

    private function fork()
    {
        $pid = pcntl_fork();
        if (0 === $pid) {
            // child process
            while (true) {
                $peerAddr = '';
                $peerPort = 0;

                $socket = socket_accept($this->connection);
                $pid = posix_getpid();
                socket_getpeername($socket, $peerAddr, $peerPort);
                $peerAddr .= ':'.(0 !== $peerPort ?: '');
                echo "Accepted {$socket} by process {$pid} from {$peerAddr}".PHP_EOL;

                $str = trim(socket_read($socket, 2048));
                try {
                    /** @var BracketsCounter $executor */
                    $executor = new $this->executor($str);
                    $result = $executor->check() ? 'true' : 'false';
                } catch (\InvalidArgumentException $exception) {
                    $result = 'Incorrect string';
                }

                socket_write($socket, "[{$result}]".PHP_EOL);
                socket_close($socket);
            }
        }
    }

    /**
     * @param $address
     * @param $port
     * @return resource
     * @throws ServerConnectionException
     */
    private function connect($address, $port)
    {
        $acceptor = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (false === $acceptor) {
            throw new ServerConnectionException("Socket create failed: ".socket_strerror(socket_last_error()), 1);

        }
        socket_set_option($acceptor, SOL_SOCKET, SO_REUSEADDR, 1); // для снятия блокировки после предыдущего пользования сокетом
        if (!socket_bind($acceptor, $address, $port)) {
            throw new ServerConnectionException("Socket bind failed: ".socket_strerror(socket_last_error()), 1);
        }
        if (!socket_listen($acceptor, 1)) {
            throw new ServerConnectionException("Socket listen failed: ".socket_strerror(socket_last_error()), 1);
        }

        return $acceptor;
    }
}