<?php

namespace ryzerbe\core\anticheat\type;

use pocketmine\entity\Effect;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\anticheat\AntiCheatManager;
use ryzerbe\core\anticheat\AntiCheatPlayer;
use ryzerbe\core\anticheat\Check;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\embed\DiscordEmbed;
use ryzerbe\core\util\discord\embed\options\EmbedField;
use ryzerbe\core\util\discord\WebhookLinks;
use function floor;
use function implode;
use function microtime;
use function strval;

class Nuker extends Check {

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $acPlayer = AntiCheatManager::getPlayer($player);
        $action = $event->getAction();
        if($acPlayer === null) return;

        if($action === PlayerInteractEvent::LEFT_CLICK_BLOCK) $acPlayer->breakTime = floor(microtime(true) * 20);
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event){
        if(!$event->getInstaBreak()){
            if($event->isCancelled()) return;
            $player = $event->getPlayer();
            $acPlayer = AntiCheatManager::getPlayer($player);
            if($acPlayer === null) return;
            $acPlayer->breakCount++;
            if($acPlayer->breakCount < 5) return;
            if($acPlayer->breakTime === -1){
                $this->sendWarningMessage($player);
                $event->setCancelled();
                return;
            }
            $target = $event->getBlock();
            $item = $event->getItem();

            $expectedTime = ceil($target->getBreakTime($item) * 20);

            if($player->hasEffect(Effect::HASTE)){
                $expectedTime *= 1 - (0.2 * $player->getEffect(Effect::HASTE)->getEffectLevel());
            }

            if($player->hasEffect(Effect::MINING_FATIGUE)){
                $expectedTime *= 1 + (0.3 * $player->getEffect(Effect::MINING_FATIGUE)->getEffectLevel());
            }

            $expectedTime -= 1;

            $actualTime = ceil(microtime(true) * 20) - $acPlayer->breakTime;

            if($actualTime < $expectedTime){
                $event->setCancelled();
                $this->sendWarningMessage($player, $expectedTime, $actualTime);
                return;
            }

            $acPlayer->breakTime = -1;
        }
    }

    /**
     * @param Player $player
     * @param float|int $expectedTime
     * @param float|int $actualTime
     */
    public function sendWarningMessage(Player $player, float|int $expectedTime = -1, float|int $actualTime = -1): void{
        $antiCheatPlayer = AntiCheatManager::getPlayer($player);
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($antiCheatPlayer === null || $ryzerPlayer === null) return;
        if($expectedTime === -1) $expectedTime = "NO START-STOP ACTION";
        if($actualTime === -1) $actualTime = "NO START-STOP ACTION";

        $discordMessage = new DiscordMessage(WebhookLinks::AUTOCLICKER_LOG);
        $embed = new DiscordEmbed();
        $embed->setTitle("Nuker Detection");
        $embed->addField(new EmbedField("Player", $player->getName(), true));
        $embed->addField(new EmbedField("Device", $ryzerPlayer->getLoginPlayerData()->getDeviceOsName(), true));
        $embed->addField(new EmbedField("Warnings", strval($antiCheatPlayer->getWarnings($this)), true));
        $embed->addField(new EmbedField("ExpectedTime", strval($expectedTime), true));
        $embed->addField(new EmbedField("ActualTime", strval($actualTime), true));
        $embed->addField(new EmbedField("Server", Server::getInstance()->getMotd(), true));
        $embed->addField(new EmbedField("TPS", Server::getInstance()->getTicksPerSecondAverage() . " (" . Server::getInstance()->getTickUsageAverage() . "%)", true));
        $embed->setColor(match ($antiCheatPlayer->getWarnings($this)) {
            1 => DiscordColor::ORANGE,
            2 => DiscordColor::RED,
            default => DiscordColor::DARK_RED
        });
        $embed->setFooter("RyZerBE", "https://images-ext-2.discordapp.net/external/Pvz56xrz36E9uwwoKvZWm-WN2XGFk15m-GmF3ckaP_8/%3Fwidth%3D703%26height%3D703/https/media.discordapp.net/attachments/693494109842833469/730816117311930429/RYZER_Network.png");
        $discordMessage->addEmbed($embed);
        $discordMessage->send();

        $warnings = $antiCheatPlayer->getWarnings($this, 30);
        $calls = match (true) {
            $warnings <= 5 => TextFormat::GREEN.":-)",
            $warnings <= 10 => TextFormat::YELLOW.":|",
            $warnings <= 15 => TextFormat::RED.":(",
            $warnings <= 20 => TextFormat::RED."BAN",
        };
        $content = [];
        $content[] = "\n";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::RED.TextFormat::BOLD."AntiCheat ".TextFormat::RESET.TextFormat::DARK_GRAY." «";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Player: ".TextFormat::RED.$player->getName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Module: ".TextFormat::GOLD."Nuker";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Device: ".TextFormat::GOLD.$ryzerPlayer->getLoginPlayerData()->getDeviceOsName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."ExpectedTime: ".TextFormat::GOLD.$expectedTime;
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."ActualTime: ".TextFormat::GOLD.$actualTime;
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Server: ".TextFormat::RED.Server::getInstance()->getMotd();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Calls(last 30 sec): ".$calls;
        $content[] = "\n";
        StaffProvider::sendMessageToStaffs(implode("\n", $content), false);
    }

    /**
     * @return int
     */
    public function getMinWarningsPerReport(): int{
        return 5;
    }

    /**
     * @return int
     */
    public function getMaxWarnings(): int{
        return 20;
    }

    /**
     * @param AntiCheatPlayer $antiCheatPlayer
     * @return string
     */
    public function getImportance(AntiCheatPlayer $antiCheatPlayer): string{
        return "";
    }

    public function onUpdate(int $currentTick): bool{
        if(($currentTick % 20) === 0) {
            foreach(AntiCheatManager::getPlayers() as $player) $player->breakCount = 0;
        }
        return true;
    }
}