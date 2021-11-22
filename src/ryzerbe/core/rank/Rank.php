<?php

namespace ryzerbe\core\rank;

use mysqli;
use ryzerbe\core\util\async\AsyncExecutor;
use function array_search;
use function implode;

class Rank {
    private string $rankName;
    private string $nameTag;
    private string $chatPrefix;
    private string $color;
    private int $joinPower;
    private array $permissions;
    /** @var string|null */
    private ?string $duration = null;

    public function __construct(string $rankName, string $nameTag, string $chatPrefix, string $color, int $joinPower, array $permissions){
        $this->color = $color;
        $this->rankName = $rankName;
        $this->nameTag = $nameTag;
        $this->chatPrefix = $chatPrefix;
        $this->joinPower = $joinPower;
        $this->permissions = $permissions;
    }

    public function getColor(): string{
        return $this->color;
    }

    public function getChatPrefix(): string{
        return $this->chatPrefix;
    }

    public function getJoinPower(): int{
        return $this->joinPower;
    }

    public function getNameTag(): string{
        return $this->nameTag;
    }

    public function getPermissions(): array{
        return $this->permissions;
    }

    public function getPermissionFormat(): array{
        return RankManager::getInstance()->convertPermFormat($this->permissions);
    }

    public function getRankName(): string{
        return $this->rankName;
    }

    public function addPermission(string $permission, bool $mysql = false): void{
        $this->permissions[] = $permission;
        if(!$mysql) return;

        $permString = implode(":", $this->permissions);
        $rankName = $this->rankName;
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($permString, $rankName): void{
            $mysqli->query("UPDATE `ranks` SET permissions='$permString' WHERE rankname='$rankName'");
        });
    }

    public function removePermission(string $permission, bool $mysql = false): void{
        unset($this->permissions[array_search($permission, $this->permissions)]);
        if(!$mysql) return;

        $rankName = $this->rankName;
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($permission, $rankName): void{
            $mysqli->query("UPDATE `ranks` SET permissions='$permission' WHERE rankname='$rankName'");
        });
    }

    public function setJoinPower(int $joinPower, bool $mysql = false): void{
        $this->joinPower = $joinPower;
        $rankName = $this->rankName;
        if(!$mysql) return;

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($joinPower, $rankName): void{
            $mysqli->query("UPDATE `ranks` SET joinpower='$joinPower' WHERE rankname='$rankName'");
        });
    }

    public function setChatPrefix(string $chatPrefix): void{
        $this->chatPrefix = $chatPrefix;
    }

    public function setColor(string $color): void{
        $this->color = $color;
    }

    public function setNameTag(string $nameTag): void{
        $this->nameTag = $nameTag;
    }

    /**
     * @return string|null
     */
    public function getDuration(): ?string{
        return $this->duration;
    }

    /**
     * @param string|null $duration
     */
    public function setDuration(?string $duration): void{
        $this->duration = $duration;
    }
}