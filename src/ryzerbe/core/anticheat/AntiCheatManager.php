<?php

declare(strict_types=1);

namespace ryzerbe\core\anticheat;

use BauboLP\Cloud\Provider\CloudProvider;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use ReflectionException;
use ryzerbe\core\anticheat\command\CheckKillAuraCommand;
use ryzerbe\core\anticheat\command\LiveClicksCommand;
use ryzerbe\core\anticheat\command\ModuleInfoCommand;
use ryzerbe\core\anticheat\entity\KillAuraBot;
use ryzerbe\core\anticheat\type\AirJump;
use ryzerbe\core\anticheat\type\EditionFaker;
use ryzerbe\core\anticheat\type\Fly;
use ryzerbe\core\anticheat\type\KillAura;
use ryzerbe\core\anticheat\type\Nuker;
use ryzerbe\core\anticheat\type\Speed;
use ryzerbe\core\RyZerBE;
use function explode;
use function in_array;
use function scandir;
use function str_contains;
use function str_replace;

class AntiCheatManager {
    use SingletonTrait;

    public const PREFIX = TextFormat::DARK_GRAY."» ".TextFormat::RED."AntiCheat ".TextFormat::RESET;

    /** @var AntiCheatPlayer[]  */
    protected static array $players = [];

    /** @var Check[]  */
    protected static array $updatingChecks = [];
    /** @var Check[]  */
    protected static array $registeredChecks = [];

    const BLACKLIST_GROUPS = [
        "EloCWBW"
    ];

    public function __construct(){
        Server::getInstance()->getCommandMap()->registerAll("anticheat", [
            new CheckKillAuraCommand(),
            new ModuleInfoCommand()
        ]);
        Entity::registerEntity(KillAuraBot::class, TRUE);

        if(in_array(explode("-", CloudProvider::getServer())[0] ?? "Lobby", self::BLACKLIST_GROUPS)){
            Server::getInstance()->getLogger()->warning("AntiCheat disabled because the cloud group is blacklisted!");
            return;
        }

        self::registerChecks(
        # new AutoClicker(),//iTzFreeHD: AntiAutoClicker Plugin
            new Nuker(),
            new EditionFaker(),
            new KillAura(),
            new AirJump(),
            new Speed(),
            new Fly()
        );
    }

    public static function addPlayer(Player $player): void {
        self::$players[$player->getName()] = new AntiCheatPlayer($player);
    }

    public static function removePlayer(Player $player): void {
        unset(self::$players[$player->getName()]);
    }

    public static function getPlayer(Player|string $player): ?AntiCheatPlayer {
        if($player instanceof Player) $player = $player->getName();
        return self::$players[$player] ?? null;
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

    public static function isCheckRegistered(Check|string $check): bool{
        if($check instanceof Check) $check = $check::class;
        return isset(self::$registeredChecks[$check]);
    }

    /**
     * @return Check[]
     */
    public static function getRegisteredChecks(): array{
        return self::$registeredChecks;
    }

    /**
     * @throws ReflectionException
     */
    public static function getModulesIgnoreRegister(): array{
        $modules = [];

        foreach(scandir(__DIR__."/type/") as $module){
            if($module === "." || $module === "..") continue;

            $dir = str_replace([RyZerBE::$file."src/", "/"], ["", "\\"], __DIR__."/type/");
            $refClass = new ReflectionClass($dir.str_replace(".php", "", $module));
            $class = new ($refClass->getName());
            if($class instanceof Check){
                $modules[str_replace(".php", "", $module)] = $class::class;
            }
        }

        return $modules;
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