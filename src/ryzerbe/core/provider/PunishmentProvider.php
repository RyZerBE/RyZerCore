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
use ryzerbe\core\util\discord\WebhookLinks;
use ryzerbe\core\util\embed\DiscordEmbed;
use ryzerbe\core\util\embed\options\EmbedField;
use ryzerbe\core\util\punishment\PunishmentReason;

class PunishmentProvider implements RyZerProvider {

    /** @var PunishmentReason[]  */
    public static array $punishmentReasons = [];

    /**
     * @return PunishmentReason[]
     */
    public static function getPunishmentReasons(): array{
        return self::$punishmentReasons;
    }

    /**
     * @param int $id
     * @return PunishmentReason|null
     */
    public static function getPunishmentReasonById(int $id): ?PunishmentReason{
        return self::$punishmentReasons[$id - 1] ?? null;
    }

    public static function loadReasons(): void{
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli): array{
            $res = $mysqli->query("SELECT * FROM punishids");
            $punishData = [];
            if($res->num_rows > 0) {
                while($data = $res->fetch_assoc()) {
                    $punishData[$data["reason"]] = ["days" => $data["days"], "hours" => $data["hours"], "type" => $data["type"]];
                }
            }

            return $punishData;
        }, function(Server $server, array $punishData): void{
            foreach($punishData as $name => $data) {
                PunishmentProvider::addReason(new PunishmentReason($name, $data["days"], $data["hours"], $data["type"]));
            }
        });
    }

    /**
     * @param PunishmentReason $punishmentReason
     */
    public static function addReason(PunishmentReason $punishmentReason): void{
        self::$punishmentReasons[$punishmentReason->getReasonName()] = $punishmentReason;
    }

    /**
     * @param PunishmentReason|string $punishmentReason
     */
    public static function removeReason(PunishmentReason|string $punishmentReason): void{
        if($punishmentReason instanceof PunishmentReason) $punishmentReason = $punishmentReason->getReasonName();

        unset(self::$punishmentReasons[$punishmentReason]);
    }

    /**
     * @param string $playerName
     * @param string $staff
     * @param PunishmentReason $reason
     * @throws Exception
     */
    public static function punishPlayer(string $playerName, string $staff, PunishmentReason $reason){
        $reasonName = $reason->getReasonName();
        $type = $reason->getType();

        $unbanFormat = $reason->toPunishmentTime();
        if($unbanFormat instanceof DateTime)
            $unbanFormat = $unbanFormat->format("Y-m-d H:i:s");
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $staff, $unbanFormat, $type, $reasonName): void{
            $mysqli->query("INSERT INTO `punishments`(`player`, `created_by`, `until`, `type`, `reason`) VALUES ('$playerName', '$staff', '$unbanFormat', '$type', '$reasonName')");
        }, function(Server $server, $result) use ($type, $reasonName, $playerName, $staff, $unbanFormat): void{

            $discordMessage = new DiscordMessage(WebhookLinks::PUNISHMENT_LOG);
            $discordEmbed = new DiscordEmbed();
            $discordEmbed->setTitle(($type === PunishmentReason::BAN) ? $playerName." wurde gebannt" : $playerName." wurde gemutet");
            $discordEmbed->setColor(DiscordColor::RED);
            $discordEmbed->setFooter("RyZerBE Moderation", "https://media.discordapp.net/attachments/602115215307309066/907944961037729792/rbe_logo_new.png?width=702&height=702");
            $discordEmbed->setThumbnail("https://media.discordapp.net/attachments/602115215307309066/907946777951502336/unknown.png?width=720&height=486");
            $discordEmbed->addField(new EmbedField(":detective: Bad boy", $playerName, true));
            $discordEmbed->addField(new EmbedField(":dagger: Reason", $reasonName, true));
            $discordEmbed->addField(new EmbedField(":cop: Moderator", $staff, false));
            $discordEmbed->addField(new EmbedField(":hourglass: Until", ($unbanFormat === 0) ? "PERMANENT" : $unbanFormat, true));
            $discordEmbed->setDateTime(new DateTime());
            $discordMessage->addEmbed($discordEmbed);
            $discordMessage->send();

            $pk = new PlayerDisconnectPacket();
            $pk->addData("playerName", $playerName);
            $pk->addData("reason", TextFormat::RED."You have been banned from our network!");
            CloudBridge::getInstance()->getClient()->getPacketHandler()->writePacket($pk);

            $player = $server->getPlayerExact($staff);
            if($player === null) return;
            $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Spieler ".TextFormat::GOLD.$playerName.TextFormat::GRAY." wurde für ".TextFormat::GOLD.$reasonName.TextFormat::GREEN.(($type === PunishmentReason::BAN) ? " gebannt" : " gemutet"));
        });
    }

    /**
     * @param string $playerName
     * @param string $staff
     * @param string $reason
     * @param int $type
     */
    public static function unpunishPlayer(string $playerName, string $staff, string $reason, int $type){
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli) use ($playerName, $reason, $staff, $type): void{
            $mysqli->query("UPDATE `punishements` SET unban='unban#$staff#$reason' WHERE player='$xboxId' AND type='$type' AND unban not like 'unban%'");
        }, function(Server $server, $result) use ($playerName, $reason, $staff, $type): void{
            $typeString = $type === PunishmentReason::BAN ? "entbannt" : "entmutet";
            $discordMessage = new DiscordMessage(WebhookLinks::PUNISHMENT_LOG);
            $discordEmbed = new DiscordEmbed();
            $discordEmbed->setTitle($playerName." wurde ".$typeString);
            $discordEmbed->setColor(DiscordColor::RED);
            $discordEmbed->setFooter("RyZerBE Moderation", "https://media.discordapp.net/attachments/602115215307309066/907944961037729792/rbe_logo_new.png?width=702&height=702");
            $discordEmbed->setThumbnail("https://media.discordapp.net/attachments/602115215307309066/907945456137555988/7191_unban_hammer.png?width=200&height=200");
            $discordEmbed->addField(new EmbedField(":detective: Bad boy", $playerName, true));
            $discordEmbed->addField(new EmbedField(":cop: Moderator", $staff, true));
            $discordEmbed->addField(new EmbedField(":dagger: Reason", $reason, false));
            $discordEmbed->setDateTime(new DateTime());
            $discordMessage->addEmbed($discordEmbed);
            $discordMessage->send();

            $player = $server->getPlayerExact($staff);
            if($player === null) return;
            $player->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Spieler ".TextFormat::GOLD.$playerName.TextFormat::GRAY." wurde für den Grund ".TextFormat::GOLD.$reason.TextFormat::GREEN." ".$typeString);
        });
    }

    /**
     * @param $datetime
     * @throws Exception
     * @return bool
     */
    public static function activatePunishment($datetime): bool
    {
        if($datetime == "0") return true; //PERMANENT
        if(!$datetime instanceof DateTime) {
            if(stripos($datetime, "unban") !== false) return false;
            $datetime = new DateTime($datetime);
        }

        $now = new DateTime();
        if($now > $datetime) return false;

        return true;
    }

    /**
     * @param string $unbanFormat
     * @throws Exception
     * @return string
     */
    public static function getUntilFormat(string $unbanFormat): string
    {
        if ($unbanFormat == 0) return "PERMANENT";

        $diff = (new DateTime())->diff(new DateTime($unbanFormat));
        $month = $diff->m;
        $days = $diff->d;
        $hours = $diff->h;
        $minutes = $diff->i;

        $until = [];

        if ($month > 0)
            $until[] = TextFormat::RED . $month . TextFormat::GREEN . " Months";
        if ($days > 0)
            $until[] = TextFormat::RED . $days . TextFormat::GREEN . " Days";
        if ($hours > 0)
            $until[] = TextFormat::RED . $hours . TextFormat::GREEN . " Hours";
        if ($minutes > 0)
            $until[] = TextFormat::RED . $minutes . TextFormat::GREEN . " Minutes";

        return implode(", ", $until);
    }
}