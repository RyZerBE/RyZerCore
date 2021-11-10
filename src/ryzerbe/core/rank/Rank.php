<?php

namespace ryzerbe\core\rank;

use mysqli;
use pocketmine\permission\PermissionManager;
use ryzerbe\core\util\async\AsyncExecutor;
use function array_search;
use function implode;

class Rank {
    /** @var string  */
    private string $rankName;
    /** @var string  */
    private string $nameTag;
    /** @var string  */
    private string $chatPrefix;
    /** @var string  */
    private string $color;
    /** @var int  */
    private int $joinPower;
    /** @var array  */
    private array $permissions;

    /**
     * @param string $rankName
     * @param string $nameTag
     * @param string $chatPrefix
     * @param string $color
     * @param int $joinPower
     * @param array $permissions
     */
    public function __construct(string $rankName, string $nameTag, string $chatPrefix, string $color, int $joinPower, array $permissions){
        $this->color = $color;
        $this->rankName = $rankName;
        $this->nameTag = $nameTag;
        $this->chatPrefix = $chatPrefix;
        $this->joinPower = $joinPower;
        $this->permissions = $permissions;
    }

    /**
     * @return string
     */
    public function getColor(): string{
        return $this->color;
    }

    /**
     * @return string
     */
    public function getChatPrefix(): string{
        return $this->chatPrefix;
    }

    /**
     * @return int
     */
    public function getJoinPower(): int{
        return $this->joinPower;
    }

    /**
     * @return string
     */
    public function getNameTag(): string{
        return $this->nameTag;
    }

    /**
     * @return array
     */
    public function getPermissions(): array{
        return $this->permissions;
    }

    /**
     * @return array
     */
    public function getPermissionFormat(): array{
        return RankManager::getInstance()->convertPermFormat($this->permissions);
    }

    /**
     * @return string
     */
    public function getRankName(): string{
        return $this->rankName;
    }

    /**
     * @param string $permission
     * @param bool $mysql
     */
    public function addPermission(string $permission, bool $mysql = false): void{
        $this->permissions[] = $permission;
        if(!$mysql) return;

        $permString = implode(";", $this->permissions);
        $rankName = $this->rankName;
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($permString, $rankName): void{
            $mysqli->query("UPDATE `ranks` SET permissions='$permString' WHERE rankname='$rankName'");
        });
    }

    /**
     * @param string $permission
     * @param bool $mysql
     */
    public function removePermission(string $permission, bool $mysql = false): void{
        unset($this->permissions[array_search($permission, $this->permissions)]);
        if(!$mysql) return;

        $rankName = $this->rankName;
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($permission, $rankName): void{
            $mysqli->query("UPDATE `ranks` SET permissions='$permission' WHERE rankname='$rankName'");
        });
    }

    /**
     * @param int $joinPower
     * @param bool $mysql
     */
    public function setJoinPower(int $joinPower, bool $mysql = false): void{
        $this->joinPower = $joinPower;
        $rankName = $this->rankName;
        if(!$mysql) return;

        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($joinPower, $rankName): void{
            $mysqli->query("UPDATE `ranks` SET joinpower='$joinPower' WHERE rankname='$rankName'");
        });
    }

    /**
     * @param string $chatPrefix
     */
    public function setChatPrefix(string $chatPrefix): void{
        $this->chatPrefix = $chatPrefix;
    }

    /**
     * @param string $color
     */
    public function setColor(string $color): void{
        $this->color = $color;
    }

    /**
     * @param string $nameTag
     */
    public function setNameTag(string $nameTag): void{
        $this->nameTag = $nameTag;
    }
}