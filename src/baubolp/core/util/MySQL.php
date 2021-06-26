<?php


namespace baubolp\core\util;


use baubolp\core\provider\MySQLProvider;
use mysqli;

class MySQL
{
    /** @var string */
    private $host;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var string */
    private $database;
    /** @var string */
    private $index;
    /** @var \mysqli */
    private $sql;

    /**
     * MySQL constructor.
     *
     * @param string $index
     * @param string $host
     * @param string $database
     * @param string $password
     * @param string $username
     */
    public function __construct(string $index, string $host, string $database, string $password, string $username)
    {
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->host = $host;
        $this->index = $index;

        $mysqli = new mysqli($host, $username, $password, $database);
        $this->sql = $mysqli;
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return \mysqli
     */
    public function getSql(): mysqli
    {
        return $this->sql;
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    public function register()
    {
        MySQLProvider::addMySQLConnections($this->getIndex(), $this);
    }
}