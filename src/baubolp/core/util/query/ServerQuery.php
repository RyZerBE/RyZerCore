<?php

namespace baubolp\core\util\query;

use function explode;
use function fclose;
use function fread;
use function fsockopen;
use function fwrite;
use function pack;
use function stream_set_blocking;
use function stream_set_timeout;
use function strlen;
use function substr;
use function time;

class ServerQuery {

    /** @var string  */
    private string $address;
    /** @var int  */
    private int $port;

    /** @var int  */
    private int $timeout = 4;

    /**
     * ServerQuery constructor.
     * @param string $address
     * @param int $port
     */
    public function __construct(string $address, int $port){
        $this->address = $address;
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getTimeout(): int{
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getAddress(): string{
        return $this->address;
    }

    /**
     * @return int
     */
    public function getPort(): int{
        return $this->port;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void{
        $this->timeout = $timeout;
    }

    /**
     * @return QueryResult|null
     */
    public function run(): ?QueryResult {
        $socket = @fsockopen('udp://'.$this->address, $this->port, $errno, $errstr, $this->getTimeout());
        if($errno && $socket !== false){
            @fclose($socket);
            return null;
        }elseif($socket === false){
            return null;
        }
        stream_set_timeout($socket, $this->getTimeout());
        stream_set_blocking($socket, true);
        $id = pack('c*', 0x00, 0xFF, 0xFF, 0x00, 0xFE, 0xFE, 0xFE, 0xFE, 0xFD, 0xFD, 0xFD, 0xFD, 0x12, 0x34, 0x56, 0x78);
        $command = pack('cQ', 0x01, time());
        $command .= $id;
        $command .= pack('Q', 2);
        $length = strlen($command);
        if($length !== fwrite($socket, $command, $length)) return null;
        $data = fread($socket, 4096);
        fclose($socket);
        if(empty($data) || $data === false) return null;
        if(substr($data, 0, 1) !== "\x1C") return null;
        if(substr($data, 17, 16) !== $id) return null;
        $data = substr($data, 35);
        $data = explode(';', $data);

        return new QueryResult($this, $data[0], $data[1], $data[3], $data[4], $data[5], $data[7], $data[8]);
    }
}