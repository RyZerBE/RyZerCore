<?php


namespace baubolp\core\module\TrollSystem;


use baubolp\core\module\TrollSystem\commands\TrollCommand;
use baubolp\core\module\TrollSystem\events\InteractListener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class TrollSystem
{

    /** @var null|PluginBase */
    private static $instance = null;
    /** @var string[]  */
    public $trollPlayers = [];

     public $antiDrop = [];
     public $alone = [];
     public $vanish = [];

    const Prefix = TextFormat::DARK_PURPLE.TextFormat::BOLD."Troll ".TextFormat::RESET.TextFormat::GRAY;

    public function enable(PluginBase $plugin): void {
        if(!self::isEnabled()) {
            $plugin->getLogger()->info(TextFormat::GREEN."TrollSystem were enabled!");
            self::$instance = $plugin;
            $this->registerEvents();
            $this->registerCommands();
        }else {
            $plugin->getLogger()->error("TrollSystem already enabled!");
        }
    }

    protected function registerEvents() {
        $events = [
            new InteractListener()
        ];

        foreach ($events as $event) {
            Server::getInstance()->getPluginManager()->registerEvents($event, self::$instance);
        }
    }

    protected function registerCommands() {
        $commands = [
             new TrollCommand()
        ];

        foreach ($commands as $command) {
            Server::getInstance()->getCommandMap()->register('troll', $command);
        }
    }

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return self::$instance != null;
    }

    /**
     * @return \pocketmine\plugin\PluginBase|null
     */
    public function getPluginInstance(): ?\pocketmine\plugin\PluginBase
    {
        return self::$instance;
    }

    /**
     * @return string[]
     */
    public function getTrollPlayers(): array
    {
        return $this->trollPlayers;
    }

    /**
     * @param string $playerName
     */
    public function addTrollPlayer(string $playerName)
    {
        $this->trollPlayers[] = $playerName;
    }

    /**
     * @param string $playerName
     */
    public function removeTrollPlayer(string $playerName)
    {
        if(!in_array($playerName, $this->getTrollPlayers())) return;
        unset($this->trollPlayers[array_search($playerName, $this->trollPlayers)]);
    }

}