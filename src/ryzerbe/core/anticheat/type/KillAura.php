<?php

namespace ryzerbe\core\anticheat\type;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\anticheat\AntiCheatManager;
use ryzerbe\core\anticheat\AntiCheatPlayer;
use ryzerbe\core\anticheat\Check;
use ryzerbe\core\anticheat\entity\KillAuraBot;
use ryzerbe\core\player\PMMPPlayer;
use ryzerbe\core\player\RyZerPlayerProvider;
use ryzerbe\core\provider\StaffProvider;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\embed\DiscordEmbed;
use ryzerbe\core\util\discord\embed\options\EmbedField;
use ryzerbe\core\util\discord\WebhookLinks;
use function implode;
use function strval;

class KillAura extends Check {

    public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if($player->isClosed()) return;
        $acPlayer = AntiCheatManager::getPlayer($player);
        if($acPlayer === null) return;

        if($acPlayer->killAuraBot === null) return;
        $acPlayer->killAuraBot->moveToPlayer($player);
    }

    public function onDamage(EntityDamageByEntityEvent $event){
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        if(!$damager instanceof PMMPPlayer) return;
        $acPlayer = AntiCheatManager::getPlayer($damager);
        if($acPlayer === null) return;

        if($entity instanceof KillAuraBot) {
            $acPlayer->countKillAura();
            if($acPlayer->getKillAuraCount() > 2) {
                $this->sendWarningMessage($damager);
                $acPlayer->resetKillAuraCount();
                $damager->kickFromProxy(AntiCheatManager::PREFIX.TextFormat::YELLOW."Please deactivate your hacks!");
            }
            return;
        }
        if(!$entity instanceof Player) return;

        $distance = $damager->distance($entity);
        if ($distance > 4.5 && $event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
            $event->setCancelled();
        }

        $microTime = microtime(true);
        $acPlayer->hitEntityCount[$entity->getName()] = $microTime;
        if ($acPlayer->lastHitCheck === null) {
            $acPlayer->lastHitCheck = $microTime;
            return;
        }

        if ($microTime - $acPlayer->lastHitCheck < 0.01) {
            if (count($acPlayer->hitEntityCount) >= 2) {
                if ($acPlayer->getKillAuraCount() >= 5) {
                    $acPlayer->resetKillAuraCount();
                    #$this->sendWarningMessage($damager);
                    $this->spawnBotToPlayer($acPlayer);
                }
                $acPlayer->countKillAura();
            }
            if (count($acPlayer->hitEntityCount) >= 5) {
                $acPlayer->addWarning($this);
                $this->spawnBotToPlayer($acPlayer);
            }
            $acPlayer->resetHitCount();
        }
        $acPlayer->resetLastHitCheck();
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
        $embed->setTitle("KillAura Detection");
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
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Module: ".TextFormat::GOLD."KillAura";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Device: ".TextFormat::GOLD.$ryzerPlayer->getLoginPlayerData()->getDeviceOsName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Server: ".TextFormat::RED.Server::getInstance()->getMotd();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Calls: ".TextFormat::RED.$calls;
        $content[] = "\n";
        StaffProvider::sendMessageToStaffs(implode("\n", $content), false);
    }

    /**
     * @param AntiCheatPlayer $checkedPlayer
     * @param bool $hasKillAura
     */
    public function sendKillAuraBotResult(AntiCheatPlayer $checkedPlayer, bool $hasKillAura): void{
        $content = [];
        $content[] = "\n";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::RED.TextFormat::BOLD."AntiCheat ".TextFormat::GOLD."RESULT".TextFormat::RESET.TextFormat::DARK_GRAY." «";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Player: ".TextFormat::RED.$checkedPlayer->getPlayer()->getName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Module: ".TextFormat::GOLD."KillAura";
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Device: ".TextFormat::GOLD.$checkedPlayer->getPlayer()->getRyZerPlayer()->getLoginPlayerData()->getDeviceOsName();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Server: ".TextFormat::RED.Server::getInstance()->getMotd();
        $content[] = TextFormat::DARK_GRAY."» ".TextFormat::GRAY."Check: ".($hasKillAura === true) ? TextFormat::GREEN.TextFormat::BOLD."DETECTED" : TextFormat::RED."NOT DETECTED";
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

    /**
     * @param AntiCheatPlayer $cheatPlayer
     */
    public function spawnBotToPlayer(AntiCheatPlayer $cheatPlayer){
        if($cheatPlayer->killAuraBot !== null) return;
        $player = $cheatPlayer->getPlayer();
        $pos = $player->getDirectionPlane()->multiply(-2);
        $vector = $player->getPosition()->add(
            $pos->getX(), 1, $pos->getY()
        );
        $nbt = Entity::createBaseNBT(
            $vector->asVector3(), new Vector3(), $player->getYaw(), $player->getPitch()
        );
        $skin = $player->getSkin();
        $nbt->setTag(new CompoundTag("Skin"));
        $nbtSkin = $nbt->getCompoundTag("Skin");
        $nbtSkin->setByteArray("Data", $skin->getSkinData());
        $nbtSkin->setByteArray("CapeData", $skin->getCapeData());
        $nbtSkin->setString("GeometryName", $skin->getGeometryName());
        $nbtSkin->setByteArray("GeometryData", $skin->getGeometryData());
        $nbtSkin->setString("Name", $skin->getSkinId());
        $nbt->setString("checkPlayer", $cheatPlayer->getPlayer()->getName());
        $entity = new KillAuraBot($cheatPlayer->getPlayer()->getLevel(), $nbt);
        $entity->setNameTag("Broxstar ist FETT");
        $entity->setNameTagAlwaysVisible(true);
        $entity->spawnTo($player);
        $cheatPlayer->killAuraBot = $entity;
    }
}