<?php

declare(strict_types=1);

namespace ryzerbe\core\anticheat\type;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
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
use function in_array;
use function strval;

class AutoClicker extends Check {
    public const CHECK_DELAY = 20;

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        $player = $event->getPlayer();

        if($packet instanceof BatchPacket) {
            foreach($packet->getPackets() as $buf) {
                $pk = PacketPool::getPacket($buf);
                $this->handleDataPacket($pk, $player, true);
            }
        }
    }

    protected function handleDataPacket(DataPacket $packet, Player $player, bool $decode = false): void {
        $antiCheatPlayer = AntiCheatManager::getPlayer($player);
        if($antiCheatPlayer === null) return;
        if($decode) $packet->decode();
        if ($packet instanceof LevelSoundEventPacket){
            if(in_array($packet->sound, [LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE, LevelSoundEventPacket::SOUND_ATTACK_STRONG])){
                $antiCheatPlayer->addClick();
            }
        } elseif ($packet instanceof PlayerActionPacket){
            $item = $player->getInventory()->getItemInHand();
            if($item->hasEnchantment(Enchantment::EFFICIENCY)) return;
            $block = $player->getLevel()->getBlockAt($packet->x, $packet->y, $packet->z);
            if($block->getBreakTime($item) <= 0) return;
            if($packet->action === PlayerActionPacket::ACTION_START_BREAK){
                $antiCheatPlayer->addClick();
            }
        }
    }

    public function onUpdate(int $currentTick): bool{
        if($currentTick % self::CHECK_DELAY === 0) {
            foreach(AntiCheatManager::getPlayers() as $player) {
                $player->setClicksPerSecond($player->getClicks());
                $player->setClicks(0);

                if($player->getConsistentClicks() > AntiCheatPlayer::MIN_CLICKS && $player->hasConsistentClicks()){
                    $player->addWarning($this);
                    $player->resetClicks();
                }
            }
        }
        return true;
    }

    public function getImportance(AntiCheatPlayer $antiCheatPlayer): string {
        $cps = $antiCheatPlayer->getConsistentClicks(5);
        $player = $antiCheatPlayer->getPlayer();
        $device = (RyZerPlayerProvider::getRyzerPlayer($player)?->getLoginPlayerData()->getDeviceOsName()) ?? "Unknown";
        if (!$antiCheatPlayer->hasConsistentClicks()) {
            if ($cps >= 40 || ($antiCheatPlayer->getConsistentClicks(1) === $antiCheatPlayer->getConsistentClicks(3)
                    && $antiCheatPlayer->getConsistentClicks(3) === $antiCheatPlayer->getConsistentClicks(5))) {
                return TextFormat::DARK_RED . "3";
            }else if($device == "Android" || $device == "iOS" && $cps >= 20) {
                return TextFormat::DARK_RED."3";
            }if ($cps >= 20) {
                return TextFormat::RED."2";
            }
            return TextFormat::GOLD."1";
        }

        if($antiCheatPlayer->getConsistentClicks(1) === $antiCheatPlayer->getConsistentClicks(3)
            && $antiCheatPlayer->getConsistentClicks(3) === $antiCheatPlayer->getConsistentClicks(5) && $cps >= (AntiCheatPlayer::MIN_CLICKS * 2)) {
            return TextFormat::DARK_RED."3";
        }else if ($cps >= 20) {
            return TextFormat::RED."2";
        }
        return TextFormat::GOLD."1";
    }

    public function sendWarningMessage(Player $player, bool $ban = false): void{
        $antiCheatPlayer = AntiCheatManager::getPlayer($player);
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($antiCheatPlayer === null || $ryzerPlayer === null) return;

        $discordMessage = new DiscordMessage(WebhookLinks::AUTOCLICKER_LOG);
        $embed = new DiscordEmbed();
        $embed->setTitle(($ban ? "[AutoClicker] Player was banned" : "AutoClicker Detection"));
        $embed->addField(new EmbedField("Player", $player->getName(), true));
        $embed->addField(new EmbedField("Device", $ryzerPlayer->getLoginPlayerData()->getDeviceOsName(), true));
        $embed->addField(new EmbedField("Warnings", strval($antiCheatPlayer->getWarnings($this)), true));
        $embed->addField(new EmbedField("CPS (Last second)", strval($antiCheatPlayer->getConsistentClicks(1)), true));
        $embed->addField(new EmbedField("CPS (Last three seconds)", strval($antiCheatPlayer->getConsistentClicks(3)), true));
        $embed->addField(new EmbedField("CPS (Last five seconds)", strval($antiCheatPlayer->getConsistentClicks(5)), true));
        $embed->addField(new EmbedField("Importance", TextFormat::clean($this->getImportance($antiCheatPlayer)), true));
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
            $warnings <= 20 => TextFormat::RED."SHORTLY BEFORE BAN",
        };
        $content = [];
        $content[] = "\n";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::RED.TextFormat::BOLD."AntiCheat ".TextFormat::RESET.TextFormat::DARK_GRAY." «";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Module: ".TextFormat::GOLD."AutoClicker";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Device: ".TextFormat::GOLD.$ryzerPlayer->getLoginPlayerData()->getDeviceOsName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."CPS (Last sec): ".TextFormat::GOLD.$antiCheatPlayer->getConsistentClicks(1);
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."CPS (Last 3 secs): ".TextFormat::GOLD.$antiCheatPlayer->getConsistentClicks(3);
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."CPS (Last 5 secs): ".TextFormat::GOLD.$antiCheatPlayer->getConsistentClicks(5);
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Importance: ".TextFormat::GOLD.$this->getImportance($antiCheatPlayer);
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Calls(last 30 sec): ".$calls;
        $content[] = "\n";
        StaffProvider::sendMessageToStaffs(implode("\n", $content), false);

    }

    public function getMinWarningsPerReport(): int{
        return 10;
    }

    public function getMaxWarnings(): int {
        return 25;
    }
}