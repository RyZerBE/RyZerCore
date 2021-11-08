<?php

namespace ryzerbe\core\util\query;

class ServerQueryResult {

    /** @var ServerQuery  */
    private ServerQuery $query;

    /** @var string  */
    private string $game_name;
    /** @var string  */
    private string $host_name;
    /** @var string  */
    private string $version;
    /** @var int  */
    private int $online_players;
    /** @var int  */
    private int $max_players;
    /** @var string  */
    private string $map;
    /** @var string  */
    private string $game_mode;

    /**
     * QueryResult constructor.
     * @param ServerQuery $query
     * @param string $game_name
     * @param string $host_name
     * @param string $version
     * @param int $online_players
     * @param int $max_players
     * @param string $map
     * @param string $game_mode
     */
    public function __construct(ServerQuery $query, string $game_name, string $host_name, string $version, int $online_players, int $max_players, string $map, string $game_mode){
        $this->query = $query;
        $this->game_name = $game_name;
        $this->host_name = $host_name;
        $this->version = $version;
        $this->online_players = $online_players;
        $this->max_players = $max_players;
        $this->map = $map;
        $this->game_mode = $game_mode;
    }

    /**
     * @return ServerQuery
     */
    public function getQuery(): ServerQuery{
        return $this->query;
    }

    /**
     * @return string
     */
    public function getGameName(): string{
        return $this->game_name;
    }

    /**
     * @return string
     */
    public function getHostName(): string{
        return $this->host_name;
    }

    /**
     * @return string
     */
    public function getVersion(): string{
        return $this->version;
    }

    /**
     * @return int
     */
    public function getOnlinePlayers(): int{
        return $this->online_players;
    }

    /**
     * @return int
     */
    public function getMaxPlayers(): int{
        return $this->max_players;
    }

    /**
     * @return string
     */
    public function getMap(): string{
        return $this->map;
    }

    /**
     * @return string
     */
    public function getGameMode(): string{
        return $this->game_mode;
    }
}