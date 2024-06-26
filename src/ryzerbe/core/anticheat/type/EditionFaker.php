<?php

namespace ryzerbe\core\anticheat\type;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\anticheat\AntiCheatManager;
use ryzerbe\core\anticheat\AntiCheatPlayer;
use ryzerbe\core\anticheat\Check;
use ryzerbe\core\player\data\LoginPlayerData;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\embed\DiscordEmbed;
use ryzerbe\core\util\discord\embed\options\EmbedField;
use ryzerbe\core\util\discord\WebhookLinks;
use ryzerbe\core\util\TaskUtils;
use function implode;
use function strval;

class EditionFaker extends Check {

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void{
        $packet = $event->getPacket();
        if($packet instanceof LoginPacket){
            $input = $packet->clientData["DefaultInputMode"];
            $os = $packet->clientData["DeviceOS"];
            if($os === LoginPlayerData::ANDROID || $os === LoginPlayerData::IOS){
                if($input === LoginPlayerData::KEYBOARD){
                    $event->getPlayer()->kick(AntiCheatManager::PREFIX.TextFormat::YELLOW."Please deactivate your hacks!");
                    $name = $packet->username;
                    AsyncExecutor::submitClosureTask(TaskUtils::secondsToTicks(3), function(int $currentTick) use ($name): void{
                        if(($player = Server::getInstance()->getPlayerExact($name)) !== null){
                            if($player instanceof PMMPPlayer){
                                $player->kickFromProxy(AntiCheatManager::PREFIX.TextFormat::YELLOW."Please deactivate your hacks!");
                            }
                        }
                    });
                }
            }
        }
    }

    /**
     * @param Player $player
     */
    public function sendWarningMessage(Player $player): void{
        $antiCheatPlayer = AntiCheatManager::getPlayer($player);
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($antiCheatPlayer === null || $ryzerPlayer === null) return;

        $discordMessage = new DiscordMessage(WebhookLinks::AUTOCLICKER_LOG);
        $embed = new DiscordEmbed();
        $embed->setTitle("EditionFaker Detection");
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

        $content = [];
        $content[] = "\n";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::RED.TextFormat::BOLD."AntiCheat ".TextFormat::RESET.TextFormat::DARK_GRAY." «";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Player: ".TextFormat::RED.$player->getName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Module: ".TextFormat::GOLD."EditionFaker";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Device: ".TextFormat::GOLD.$ryzerPlayer->getLoginPlayerData()->getDeviceOsName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Server: ".TextFormat::RED.Server::getInstance()->getMotd();
        $content[] = "\n";
        StaffProvider::sendMessageToStaffs(implode("\n", $content), false);
    }

    /**
     * @return int
     */
    public function getMinWarningsPerReport(): int{
        return 0;
    }

    /**
     * @return int
     */
    public function getMaxWarnings(): int{
       return 2;
    }

    /**
     * @param AntiCheatPlayer $antiCheatPlayer
     * @return string
     */
    public function getImportance(AntiCheatPlayer $antiCheatPlayer): string{
        return "";
    }
}