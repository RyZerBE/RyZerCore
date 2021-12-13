<?php

namespace ryzerbe\core\provider;

use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Packets\PlayerDisconnectPacket;
use DateTime;
use Exception;
use mysqli;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\embed\DiscordEmbed;
use ryzerbe\core\util\discord\embed\options\EmbedField;
use ryzerbe\core\util\discord\WebhookLinks;
use ryzerbe\core\util\punishment\PunishmentReason;
use ryzerbe\core\util\Settings;
use function array_search;

class PunishmentProvider implements RyZerProvider {
    /** @var PunishmentReason[] */
    public static array $punishmentReasons = [];

    /**
     * @return PunishmentReason[]
     */
    public static function getPunishmentReasons(): array{
        return self::$punishmentReasons;
    }

    public static function getPunishmentReasonById(int $id): ?PunishmentReason{
        return self::$punishmentReasons[$id - 1] ?? null;
    }

    public static function loadReasons(): void{
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli): array{
            $res = $mysqli->query("SELECT * FROM punishids");
            $punishData = [];
            if($res->num_rows > 0){
                while($data = $res->fetch_assoc()){
                    $punishData[$data["reason"]] = [
                        "days" => $data["days"],
                        "hours" => $data["hours"],
                        "type" => $data["type"],
                    ];
                }
            }
            return $punishData;
        }, function(Server $server, array $punishData): void{
            foreach($punishData as $name => $data){
                PunishmentProvider::addReason(new PunishmentReason($name, $data["days"], $data["hours"], $data["type"]));
            }
        });
    }

    public static function addReason(PunishmentReason $punishmentReason, bool $mysql = false): void{
        self::$punishmentReasons[] = $punishmentReason;
        if($mysql) {
            $name = $punishmentReason->getReasonName();
            $days = $punishmentReason->getDays();
            $hours = $punishmentReason->getHours();
            $type = $punishmentReason->getType();
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name, $type, $hours, $days): void{
                $mysqli->query("INSERT INTO `punishids`(`reason`, `hours`, `days`, `type`) VALUES ('$name', '$hours', '$days', '$type')");
            });
        }
    }

    public static function removeReason(PunishmentReason $punishmentReason, bool $mysql = false): void{
        unset(self::$punishmentReasons[array_search($punishmentReason, self::$punishmentReasons)]);
        $name = $punishmentReason->getReasonName();
        $days = $punishmentReason->getDays();
        $hours = $punishmentReason->getHours();
        $type = $punishmentReason->getType();
        if($mysql) {
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($name, $type, $hours, $days): void{
                $mysqli->query("DELETE FROM `punishids` WHERE reason='$name' AND type='$type' AND hours='$hours' AND days='$days'");
            });
        }
    }

    /**
     * @throws Exception
     */
    public static function punishPlayer(string $playerName, string $staff, PunishmentReason|int $reason){
        if(!$reason instanceof PunishmentReason) $reason = self::getPunishmentReasonById($reason);

        $reasonName = $reason->getReasonName();
        $type = $reason->getType();
        $unbanFormat = $reason->toPunishmentTime();
        if($unbanFormat instanceof DateTime){
            $unbanFormat = $unbanFormat->format("Y-m-d H:i:s");
        }
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $staff, $unbanFormat, $type, $reasonName): ?int{
            $mysqli->query("INSERT INTO `punishments`(`player`, `created_by`, `until`, `type`, `reason`) VALUES ('$playerName', '$staff', '$unbanFormat', '$type', '$reasonName')");

            $res = $mysqli->query("SELECT id FROM `punishments` WHERE player='$playerName' AND type='$type' AND until='$unbanFormat'");
            if($res->num_rows <= 0) return null;
            $id = $res->fetch_assoc()["id"] ?? null;
            if($id === null) return null;
            $mysqli->query("INSERT INTO `proofs`(`id`, `message_id`) VALUES ('$id', '')");
            return $id;
        }, function(Server $server, ?int $id) use ($type, $reasonName, $playerName, $staff, $unbanFormat): void{
            $id = (($id === null) ? "???" : $id);
            $discordMessage = new DiscordMessage(WebhookLinks::PUNISHMENT_LOG);
            $discordEmbed = new DiscordEmbed();
            $discordEmbed->setTitle(($type === PunishmentReason::BAN) ? $playerName . " wurde gebannt" : $playerName . " wurde gemutet");
            $discordEmbed->setColor(DiscordColor::RED);
            $discordEmbed->setFooter("RyZerBE Moderation", "https://media.discordapp.net/attachments/602115215307309066/907944961037729792/rbe_logo_new.png?width=702&height=702");
            $discordEmbed->setThumbnail("https://media.discordapp.net/attachments/602115215307309066/907946777951502336/unknown.png?width=720&height=486");
            $discordEmbed->addField(new EmbedField(":detective: Bad boy", $playerName, true));
            $discordEmbed->addField(new EmbedField(":dagger: Reason", $reasonName, true));
            $discordEmbed->addField(new EmbedField(":cop: Moderator", $staff, false));
            $discordEmbed->addField(new EmbedField(":paperclip: Proof-ID", $id, false));
            $discordEmbed->addField(new EmbedField(":hourglass: Until", ($unbanFormat === 0) ? "PERMANENT" : $unbanFormat, true));
            $discordEmbed->setDateTime(new DateTime());
            $discordMessage->addEmbed($discordEmbed);
            $discordMessage->send();

            if($type === PunishmentReason::BAN){
                $pk = new PlayerDisconnectPacket();
                $pk->addData("playerName", $playerName);
                $pk->addData("message", TextFormat::RED . "You have been banned from our network!");
                CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);
            }

            StaffProvider::sendMessageToStaffs(RyZerBE::PREFIX.(($type === PunishmentReason::BAN) ? TextFormat::GOLD.$playerName . TextFormat::RED." wurde gebannt"
                : TextFormat::GOLD. $playerName . TextFormat::RED." wurde gemutet")
                ."\n".TextFormat::GRAY."Grund: ".TextFormat::GOLD.$reasonName
                ."\n".TextFormat::GRAY."Bestraft von: ".TextFormat::GOLD.$staff, true);
            $player = $server->getPlayerExact($staff);
            if($player === null) return;
            $player->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Der Spieler " . TextFormat::GOLD . $playerName . TextFormat::GRAY . " wurde für " . TextFormat::GOLD . $reasonName . TextFormat::GRAY." mit der ID ".TextFormat::YELLOW.$id." ".TextFormat::GREEN . (($type === PunishmentReason::BAN) ? " gebannt" : " gemutet"));
        });
    }

    public static function unpunishPlayer(string $playerName, string $staff, string $reason, int $type){
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $reason, $staff, $type): void{
            $mysqli->query("UPDATE `punishments` SET until='unban#$staff#$reason' WHERE player='$playerName' AND type='$type' AND until not like 'unban%'");
        }, function(Server $server, $result) use ($playerName, $reason, $staff, $type): void{
            $typeString = $type === PunishmentReason::BAN ? "entbannt" : "entmutet";
            $discordMessage = new DiscordMessage(WebhookLinks::PUNISHMENT_LOG);
            $discordEmbed = new DiscordEmbed();
            $discordEmbed->setTitle($playerName . " wurde " . $typeString);
            $discordEmbed->setColor(DiscordColor::RED);
            $discordEmbed->setFooter("RyZerBE Moderation", "https://media.discordapp.net/attachments/602115215307309066/907944961037729792/rbe_logo_new.png?width=702&height=702");
            $discordEmbed->setThumbnail("https://media.discordapp.net/attachments/602115215307309066/907945456137555988/7191_unban_hammer.png?width=200&height=200");
            $discordEmbed->addField(new EmbedField(":detective: Bad boy", $playerName, true));
            $discordEmbed->addField(new EmbedField(":cop: Moderator", $staff, true));
            $discordEmbed->addField(new EmbedField(":dagger: Reason", $reason, false));
            $discordEmbed->setDateTime(new DateTime());
            $discordMessage->addEmbed($discordEmbed);
            $discordMessage->send();
            StaffProvider::sendMessageToStaffs(RyZerBE::PREFIX.(($type === PunishmentReason::BAN) ? TextFormat::GOLD.$playerName . TextFormat::GRAY." wurde entbannt"
                    : TextFormat::GOLD. $playerName . TextFormat::GRAY." wurde entmutet")
                ."\n".TextFormat::GRAY."Grund: ".TextFormat::GOLD.$reason
                ."\n".TextFormat::GRAY."Aufgehoben von: ".TextFormat::GOLD.$staff, true);
            $player = $server->getPlayerExact($staff);
            if($player === null) return;
            $player->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Der Spieler " . TextFormat::GOLD . $playerName . TextFormat::GRAY . " wurde für den Grund " . TextFormat::GOLD . $reason . TextFormat::GREEN . " " . $typeString);
        });
    }

    /**
     * @throws Exception
     */
    public static function activatePunishment($datetime): bool{
        if($datetime == "0") return true; //PERMANENT
        if(!$datetime instanceof DateTime){
            if(stripos($datetime, "unban") !== false) return false;
            $datetime = new DateTime($datetime);
        }
        $now = new DateTime();
        if($now > $datetime) return false;
        return true;
    }

    public static function getSyncBanPoints(string $playerName): int{
        $data = Settings::$mysqlLoginData;
        $mysqli = new mysqli($data["host"], $data["user"], $data["password"], "RyZerCore");
        $res = $mysqli->query("SELECT * FROM punishments WHERE player='$playerName' AND type='" . PunishmentReason::BAN . "'");
        $mysqli->close();
        return $res->num_rows;
    }

    public static function getSyncMutePoints(string $playerName): int{
        $data = Settings::$mysqlLoginData;
        $mysqli = new mysqli($data["host"], $data["user"], $data["password"], "RyZerCore");
        $res = $mysqli->query("SELECT * FROM punishments WHERE player='$playerName' AND type='" . PunishmentReason::MUTE . "'");
        $mysqli->close();
        return $res->num_rows;
    }

    /**
     * @throws Exception
     */
    public static function getUntilFormat(string $unbanFormat): string{
        if($unbanFormat == 0) return "PERMANENT";
        $diff = (new DateTime())->diff(new DateTime($unbanFormat));
        $years = $diff->y;
        $month = $diff->m;
        $days = $diff->d;
        $hours = $diff->h;
        $minutes = $diff->i;
        $until = [];

        if($years > 0){
            $until[] = "&c" . $years . "&a" . " Years";
        }
        if($month > 0){
            $until[] = "&c" . $month . "&a" . " Months";
        }
        if($days > 0){
            $until[] = "&c" . $days . "&a" . " Days";
        }
        if($hours > 0){
            $until[] = "&c" . $hours . "&a" . " Hours";
        }
        if($minutes > 0){
            $until[] = "&c" . $minutes . "&a" . " Minutes";
        }
        return implode(", ", $until);
    }
}