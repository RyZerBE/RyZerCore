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
use ryzerbe\core\anticheat\AntiCheatManager;
use ryzerbe\core\anticheat\AntiCheatPlayer;
use ryzerbe\core\anticheat\Check;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\embed\DiscordEmbed;
use ryzerbe\core\util\discord\embed\options\EmbedField;
use ryzerbe\core\util\discord\WebhookLinks;
use function in_array;
use function strval;

class AutoClicker extends Check {
    public const CHECK_DELAY = 30;

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
        if($currentTick % self::CHECK_DELAY === 0) {//The fuck!?
            foreach(AntiCheatManager::getPlayers() as $player) {
                $player->setClicksPerSecond($player->getClicks());
                $player->setClicks(0);

                if(
                    $player->getConsistentClicks() > AntiCheatPlayer::MIN_CLICKS &&
                    $player->hasConsistentClicks()
                ){
                    $player->addWarning($this);
                    $player->resetClicks();
                }
            }
        }
        return true;
    }

    public function sendWarningMessage(Player $player): void{
        $antiCheatPlayer = AntiCheatManager::getPlayer($player);
        $ryzerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($antiCheatPlayer === null || $ryzerPlayer === null) return;
        if($antiCheatPlayer->getWarnings($this) > 3) return;

        $discordMessage = new DiscordMessage(WebhookLinks::AUTOCLICKER_LOG);
        $embed = new DiscordEmbed();
        $embed->setTitle("AutoClicker Detection");
        $embed->addField(new EmbedField("Player", $player->getName()));
        $embed->addField(new EmbedField("Device", $ryzerPlayer->getLoginPlayerData()->getDeviceOsName()));
        $embed->addField(new EmbedField("CPS (Last second)", strval($antiCheatPlayer->getConsistentClicks(1))));
        $embed->addField(new EmbedField("CPS (Last three seconds)", strval($antiCheatPlayer->getConsistentClicks(3))));
        $embed->addField(new EmbedField("Importance", "IDK"));//TODO
        $embed->addField(new EmbedField("Warnings", strval($antiCheatPlayer->getWarnings($this))));
        $embed->addField(new EmbedField("Server", Server::getInstance()->getMotd()));
        $embed->addField(new EmbedField("TPS", Server::getInstance()->getTicksPerSecondAverage() . "(" . Server::getInstance()->getTickUsageAverage() . "%)"));
        $embed->setColor(match ($antiCheatPlayer->getWarnings($this)) {
            1 => DiscordColor::ORANGE,
            2 => DiscordColor::RED,
            default => DiscordColor::DARK_RED
        });
        $embed->setFooter("RyZerBE", "https://images-ext-2.discordapp.net/external/Pvz56xrz36E9uwwoKvZWm-WN2XGFk15m-GmF3ckaP_8/%3Fwidth%3D703%26height%3D703/https/media.discordapp.net/attachments/693494109842833469/730816117311930429/RYZER_Network.png");
        $discordMessage->addEmbed($embed);
        $discordMessage->send();
    }
}