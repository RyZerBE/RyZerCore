<?php

namespace ryzerbe\core\anticheat\type;

use pocketmine\block\BlockIds;
use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
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
use function strval;

class Fly extends Check {

    public const DETECTED_FLIGHT_BLOCKS = [
        BlockIds::WATER,
        BlockIds::FLOWING_LAVA,
        BlockIds::FLOWING_WATER,
        BlockIds::LAVA,
        BlockIds::LADDER,
        BlockIds::SKULL_BLOCK,
        BlockIds::VINE,
        BlockIds::LILY_PAD
    ];

    public const DETECTED_FLIGHT_EFFECTS = [
        Effect::JUMP_BOOST,
        Effect::LEVITATION
    ];

    public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        $acPlayer = AntiCheatManager::getPlayer($player);
        if($acPlayer === null) return;
        if($acPlayer->isServerMotionSet() || $player->getAllowFlight()) return;
        if($player->getArmorInventory()->getItem(ArmorInventory::SLOT_CHEST)->getId() === ItemIds::ELYTRA) return;

        $block = $player->getLevel()->getBlock($player->asVector3());
        if(in_array($block->getId(), self::DETECTED_FLIGHT_BLOCKS)) return;
        if(in_array($player->getLevel()->getBlock($player->asVector3()->add(1))->getId(), self::DETECTED_FLIGHT_BLOCKS)) return;
        if(in_array($player->getLevel()->getBlock($player->asVector3()->add(0, 0, 1))->getId(), self::DETECTED_FLIGHT_BLOCKS)) return;
        if(in_array($player->getLevel()->getBlock($player->asVector3()->add(-1))->getId(), self::DETECTED_FLIGHT_BLOCKS)) return;
        if(in_array($player->getLevel()->getBlock($player->asVector3()->add(0, 0, -1))->getId(), self::DETECTED_FLIGHT_BLOCKS)) return;
        if(in_array($player->getLevel()->getBlock($player->asVector3()->add(-1, 0, -1))->getId(), self::DETECTED_FLIGHT_BLOCKS)) return;
        if(in_array($player->getLevel()->getBlock($player->asVector3()->add(1, 0, 1))->getId(), self::DETECTED_FLIGHT_BLOCKS)) return;
        if(in_array($player->getLevel()->getBlock($player->asVector3()->add(0, -1))->getId(), self::DETECTED_FLIGHT_BLOCKS)) return;
        foreach($player->getEffects() as $effect) if(in_array($effect->getId(), self::DETECTED_FLIGHT_EFFECTS)) return;

        if($player->fallDistance == 0){
            $acPlayer->resetMaxFlightHeight();
        }else{
            if($acPlayer->getMaxFlightHeight() < $player->getY()) $acPlayer->setMaxFlightHeight($player->getY());
        }

        if((!$player->isOnGround()
                && $player->fallDistance == 0)
            && $player->getY() > -1){
            $acPlayer->countMoveOnAir();
        }else{
            $acPlayer->resetCountsOnAir();
        }

        if($acPlayer->getMoveOnAirCount() > 10) $acPlayer->flag("Fly", $this);
    }

    public function onEntityMotion(EntityMotionEvent $event){
        $entity = $event->getEntity();
        if(!$entity instanceof Player) return;

        $acPlayer = AntiCheatManager::getPlayer($entity);
        if($acPlayer === null) return;
        $vector = $event->getVector();
        if ($vector->getX() == 0 && $vector->getY() == 0 && $vector->getZ() == 0) return;
        $acPlayer->setServerMotionSet();
    }

    public function receivePacket(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $acPlayer = AntiCheatManager::getPlayer($player);
        if($acPlayer === null) return;

        if($packet instanceof AdventureSettingsPacket){
            $isFlying = $packet->getFlag(AdventureSettingsPacket::FLYING);
            if(!$acPlayer->isServerMotionSet() && !$player->getAllowFlight() && $isFlying){
                $event->setCancelled();
                $acPlayer->flag("Fly", $this);
            }elseif($packet->getFlag(AdventureSettingsPacket::NO_CLIP) and !$player->isSpectator()){
                $acPlayer->flag("NoClip", $this);
                $event->setCancelled();
            }
        }
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

    public function onUpdate(int $currentTick): bool{
        if(($currentTick % 20) === 0) {
            foreach(AntiCheatManager::getPlayers() as $player){
                $player->lastVector = $player->getPlayer()->asVector3();
            }
        }
        return true;
    }
}