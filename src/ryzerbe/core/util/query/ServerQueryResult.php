<?php

namespace ryzerbe\core\util\query;

class ServerQueryResult {
    private ServerQuery $query;

    private string $game_name;
    private string $host_name;
    private string $version;
    private int $online_players;
    private int $max_players;
    private string $map;
    private string $game_mode;

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

    public function getQuery(): ServerQuery{
        return $this->query;
    }

    public function getGameName(): string{
        return $this->game_name;
    }

    public function getHostName(): string{
        return $this->host_name;
    }

    public function getVersion(): string{
        return $this->version;
    }

    public function getOnlinePlayers(): int{
        return $this->online_players;
    }

    public function getMaxPlayers(): int{
        return $this->max_players;
    }

    public function getMap(): string{
        return $this->map;
    }

    public function getGameMode(): string{
        return $this->game_mode;
    }
}