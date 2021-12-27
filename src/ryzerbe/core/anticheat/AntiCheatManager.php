<?php

declare(strict_types=1);

namespace ryzerbe\core\anticheat;

use BauboLP\Cloud\Provider\CloudProvider;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ryzerbe\core\anticheat\type\AutoClicker;
use ryzerbe\core\anticheat\type\EditionFaker;
use ryzerbe\core\anticheat\type\Fly;
use ryzerbe\core\anticheat\type\Nuker;
use ryzerbe\core\RyZerBE;
use function str_contains;
use function var_dump;

class AntiCheatManager {
    use SingletonTrait;

    /** @var AntiCheatPlayer[]  */
    protected static array $players = [];

    /** @var Check[]  */
    protected static array $updatingChecks = [];
    /** @var Check[]  */
    protected static array $registeredChecks = [];

    public function __construct(){
        self::registerChecks(
            new AutoClicker(),
            new Nuker(),
            new EditionFaker()
        );

        if(str_contains(CloudProvider::getServer(), "BuildFFA")){
            self::registerCheck(new Fly());
            Server::getInstance()->getLogger()->warning("BETA: Fly Module activated!");
        }
    }

    public static function addPlayer(Player $player): void {
        self::$players[$player->getName()] = new AntiCheatPlayer($player);
    }

    public static function removePlayer(Player $player): void {
        unset(self::$players[$player->getName()]);
    }

    public static function getPlayer(Player $player): ?AntiCheatPlayer {
        return self::$players[$player->getName()] ?? null;
    }

    /**
     * @return AntiCheatPlayer[]
     */
    public static function getPlayers(): array{
        return self::$players;
    }

    public static function registerCheck(Check $check): void {
        Server::getInstance()->getPluginManager()->registerEvents($check, RyZerBE::getPlugin());
        self::$registeredChecks[$check::class] = $check;
        $check->scheduleUpdate();
    }

    public static function registerChecks(Check... $checks): void {
        foreach($checks as $check) self::registerCheck($check);
    }

    /**
     * @return Check[]
     */
    public static function getRegisteredChecks(): array{
        return self::$registeredChecks;
    }

    /**
     * @return Check[]
     */
    public static function getUpdatingChecks(): array{
        return self::$updatingChecks;
    }

    public static function scheduleCheckUpdate(Check $check): void {
        self::$updatingChecks[$check::class] = $check;
    }

    public function onUpdate(int $currentTick): void {
        foreach(self::getUpdatingChecks() as $check) {
            if(!$check->onUpdate($currentTick)) {
                unset(self::$updatingChecks[$check::class]);
            }
        }
    }
}