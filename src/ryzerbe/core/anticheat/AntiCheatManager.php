<?php

declare(strict_types=1);

namespace ryzerbe\core\anticheat;

use BauboLP\Cloud\Provider\CloudProvider;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use ryzerbe\core\anticheat\command\CheckKillAuraCommand;
use ryzerbe\core\anticheat\entity\KillAuraBot;
use ryzerbe\core\anticheat\type\AirJump;
use ryzerbe\core\anticheat\type\AutoClicker;
use ryzerbe\core\anticheat\type\EditionFaker;
use ryzerbe\core\anticheat\type\Fly;
use ryzerbe\core\anticheat\type\JetPackByPass;
use ryzerbe\core\anticheat\type\KillAura;
use ryzerbe\core\anticheat\type\Nuker;
use ryzerbe\core\anticheat\type\Speed;
use ryzerbe\core\RyZerBE;
use function str_contains;

class AntiCheatManager {
    use SingletonTrait;

    public const PREFIX = TextFormat::DARK_GRAY."» ".TextFormat::RED."AntiCheat ".TextFormat::RESET;

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

        Server::getInstance()->getCommandMap()->register("anticheat", new CheckKillAuraCommand());
        Entity::registerEntity(KillAuraBot::class, TRUE);

        if(str_contains(CloudProvider::getServer(), "BuildFFA")){
            self::registerCheck(new Fly());
            self::registerCheck(new AirJump());
            self::registerCheck(new KillAura());
            self::registerCheck(new JetPackByPass());
            self::registerCheck(new Speed());
            Server::getInstance()->getLogger()->warning("BETA: Fly Module activated!");
            Server::getInstance()->getLogger()->warning("BETA: AirJump Module activated!");
            Server::getInstance()->getLogger()->warning("BETA: KillAura Module activated!");
            Server::getInstance()->getLogger()->warning("BETA: JetPackByPass Module activated!");
            Server::getInstance()->getLogger()->warning("BETA: Speed Module activated!");
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

    public static function isCheckRegistered(Check $check): bool{
        return isset(self::$registeredChecks[$check::class]);
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