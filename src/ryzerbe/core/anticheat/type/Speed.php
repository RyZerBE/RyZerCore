<?php

namespace ryzerbe\core\anticheat\type;

use pocketmine\block\BlockIds;
use pocketmine\entity\Effect;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
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
use function array_filter;
use function count;
use function implode;
use function in_array;
use function strval;
use function var_dump;

class Speed extends Check {

    public const DETECTED_SPEED_EFFECTS = [
        Effect::SPEED
    ];

    /**
     * @param Player $player
     * @param bool $ban
     */
    public function sendWarningMessage(Player $player, bool $ban = false): void{
        $antiCheatPlayer = AntiCheatManager::getPlayer($player);
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($antiCheatPlayer === null || $ryzerPlayer === null) return;

        $discordMessage = new DiscordMessage(WebhookLinks::AUTOCLICKER_LOG);
        $embed = new DiscordEmbed();
        $embed->setTitle("Speed Detection");
        $embed->addField(new EmbedField("Player", $player->getName(), true));
        $embed->addField(new EmbedField("Device", $ryzerPlayer->getLoginPlayerData()->getDeviceOsName(), true));
        $embed->addField(new EmbedField("Warnings", strval($antiCheatPlayer->getWarnings($this)), true));
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
            $warnings <= 20 => TextFormat::DARK_RED.":/",
            default => TextFormat::RED."Too much calls"
        };
        $content = [];
        $content[] = "\n";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::RED.TextFormat::BOLD."AntiCheat ".TextFormat::RESET.TextFormat::DARK_GRAY." «";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Player: ".TextFormat::RED.$player->getName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Module: ".TextFormat::GOLD."Speed";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Device: ".TextFormat::GOLD.$ryzerPlayer->getLoginPlayerData()->getDeviceOsName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Server: ".TextFormat::RED.Server::getInstance()->getMotd();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Calls: ".TextFormat::RED.$calls;
        $content[] = "\n";
        StaffProvider::sendMessageToStaffs(implode("\n", $content), false);
    }

    /**
     * @return int
     */
    public function getMinWarningsPerReport(): int{
        return 10;
    }

    /**
     * @return int
     */
    public function getMaxWarnings(): int{
        return 35;
    }

    /**
     * @param AntiCheatPlayer $antiCheatPlayer
     * @return string
     */
    public function getImportance(AntiCheatPlayer $antiCheatPlayer): string{
        return "";
    }

    public function onUpdate(int $currentTick): bool{
        if(($currentTick % 20) === 0){
            foreach(AntiCheatManager::getPlayers() as$cheatPlayer) {
                foreach($cheatPlayer->getPlayer()->getEffects() as $effect) if(in_array($effect->getId(), self::DETECTED_SPEED_EFFECTS)) continue;
                if($cheatPlayer->isServerMotionSet() || $cheatPlayer->getPlayer()->getAllowFlight() || $cheatPlayer->getPlayer()->isSwimming()) continue;
                $lastVector3 = $cheatPlayer->getLastVector3();
                $bigYDifference = $lastVector3->distance(new Vector3($lastVector3->getX(), $cheatPlayer->getPlayer()->getY(), $lastVector3->getZ()));
                if($bigYDifference > 3) continue;

                if($cheatPlayer->getPlayer()->getBlockOverPlayer()->getId() === BlockIds::AIR){
                    $blocksPerSecond = $lastVector3->distance($cheatPlayer->getPlayer()->asVector3());
                    #$cheatPlayer->getPlayer()->sendMessage("Blocks/s: $blocksPerSecond");
                    if($blocksPerSecond >= 8) {
                        $cheatPlayer->countSpeed();
                        if($cheatPlayer->getSpeedCount() > 1) {
                            $cheatPlayer->flag("Speed (".$blocksPerSecond. " Blocks/s".")", $this);
                        }
                    }else {
                        $cheatPlayer->resetSpeedCount();
                    }
                }
            }
            foreach(AntiCheatManager::getPlayers() as $player)
                $player->lastVector = $player->getPlayer()->asVector3();
        }
        return true;
    }
}