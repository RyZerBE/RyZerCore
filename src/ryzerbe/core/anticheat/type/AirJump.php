<?php

namespace ryzerbe\core\anticheat\type;

use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerJumpEvent;
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
use function implode;
use function microtime;
use function strval;

class AirJump extends Check {

    public function onJump(PlayerJumpEvent $event){
        $player = $event->getPlayer();
        if($player->isClosed()) return;

        $acPlayer = AntiCheatManager::getPlayer($player);
        if($acPlayer === null) return;
        $acPlayer->jump();
        $block = $player->getLevel()->getBlock($player->getSide(Vector3::SIDE_DOWN));
        $block2 = $player->getLevel()->getBlock($player->asVector3()->subtract(0, 2));
        if($block->getId() === BlockIds::AIR && $block2->getId() === BlockIds::AIR){
            if((microtime(true) - $acPlayer->getLastBlockPlaceTime()) < 2) return;
            $acPlayer->countAirJump();
            if($acPlayer->getAirJumpCount() > 10) $acPlayer->flag("AirJump", $this);
        }else {
            $acPlayer->resetAirJumpCount();
        }
    }

    public function onPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        $acPlayer = AntiCheatManager::getPlayer($player);
        if($acPlayer === null) return;
        $acPlayer->placeBlock();
    }

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
        $embed->setTitle($antiCheatPlayer->lastFlagReason." Detection");
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
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Module: ".TextFormat::GOLD.$antiCheatPlayer->lastFlagReason;
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
        return 30;
    }

    /**
     * @param AntiCheatPlayer $antiCheatPlayer
     * @return string
     */
    public function getImportance(AntiCheatPlayer $antiCheatPlayer): string{
        return "";
    }
}