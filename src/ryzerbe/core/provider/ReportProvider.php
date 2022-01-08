<?php

namespace ryzerbe\core\provider;

use DateTime;
use mysqli;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\language\LanguageProvider;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\embed\DiscordEmbed;
use ryzerbe\core\util\discord\embed\options\EmbedField;
use ryzerbe\core\util\discord\WebhookLinks;

class ReportProvider implements RyZerProvider {

    const PREFIX = TextFormat::BLUE.TextFormat::BOLD."Report ".TextFormat::RESET;

    const OPEN = 0;
    const PROCESS = 1;
    const PROCESSED = 2;

    const ACCEPTED = 3;
    const REJECTED = 4;

    /**
     * @param string $bad_player
     * @param string $reporter
     * @param string $reason
     * @param string $notice
     * @param string $nick
     */
    public static function createReport(string $bad_player, string $reporter, string $reason, string $notice, string $nick){
        AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function(mysqli $mysqli)use ($bad_player, $reporter, $reason, $notice, $nick): bool {
            $res = $mysqli->query("SELECT * FROM reports WHERE state='".ReportProvider::OPEN."' AND bad_player='$bad_player'");
            if($res->num_rows > 0) return false;

            $mysqli->query("INSERT INTO `reports`(`bad_player`, `created_by`, `reason`, `notice`, `nick`) VALUES ('$bad_player', '$reporter', '$reason', '$notice', '$nick')");
            return true;
        }, function(Server $server, bool $success) use ($reporter, $reason, $bad_player, $nick): void{
            $player = $server->getPlayerExact($reporter);
            if($player === null) return;
            $nick = NickProvider::getPlayerByNick($nick, true);
            $nickName = ($nick !== null) ? $nick->getNick() : $bad_player;

            if($success) {
                $player->sendMessage(RyZerBE::PREFIX.LanguageProvider::getMessageContainer("successful-player-reported", $player->getName(), ['#playername' => $nickName, '#reason' => $reason]));
                $discordMessage = new DiscordMessage(WebhookLinks::REPORT_LOG);
                $discordMessage->setMessage("<@&719213028943331359>");
                $discordEmbed = new DiscordEmbed();
                $discordEmbed->setColor(DiscordColor::RED);
                $discordEmbed->setFooter("RyZerBE Moderation", "https://media.discordapp.net/attachments/602115215307309066/907944961037729792/rbe_logo_new.png?width=702&height=702");
                $discordEmbed->setThumbnail("https://media.discordapp.net/attachments/602115215307309066/907946777951502336/unknown.png?width=720&height=486");
                $discordEmbed->addField(new EmbedField(":detective: Bad boy", $bad_player, true));
                $discordEmbed->addField(new EmbedField(":dagger: Reason", $reason, true));
                $discordEmbed->addField(new EmbedField(":cop: Detective", $reporter, false));
                $discordEmbed->addField(new EmbedField(":trident: Nick", (($nick !== null) ? "POSITIVE (".$nickName.")" : "NEGATIVE"), true));
                $discordEmbed->setDateTime(new DateTime());
                $discordMessage->addEmbed($discordEmbed);
                $discordMessage->send();
                StaffProvider::sendMessageToStaffs(
                    ReportProvider::PREFIX.TextFormat::GOLD.$bad_player.TextFormat::GRAY." wurde ".TextFormat::YELLOW."reportet".TextFormat::GRAY."!"
                    ."\n".TextFormat::GRAY."Grund: ".TextFormat::GOLD.$reason
                    ."\n".TextFormat::GRAY."Gemeldet von: ".TextFormat::GOLD.$reporter
                    ."\n".TextFormat::GRAY."Nick: ".TextFormat::GOLD.(($nick !== null) ? TextFormat::GREEN."POSITIVE".TextFormat::GRAY." - ".TextFormat::GOLD.$nickName : TextFormat::RED."NEGATIVE"), true);
            }else {
                $player->sendMessage(ReportProvider::PREFIX.LanguageProvider::getMessageContainer('player-already-reported', $player->getName(), ['#playername' => $nickName]));
            }
        });
    }

    /**
     * @param mysqli $mysqli
     * @param int $state
     * @return array
     */
    public static function getReportsByState(mysqli $mysqli, int $state = self::OPEN): array{
        $res = $mysqli->query("SELECT * FROM reports WHERE state='$state'");
        if($res->num_rows <= 0) return [];
        $reports = [];

        while($data = $res->fetch_assoc()) $reports[$data["bad_player"]] = $data;


        return $reports;
    }

    /**
     * @param mysqli $mysqli
     * @param string $player
     * @return array
     */
    public static function getReportArchiveOfPlayer(mysqli $mysqli, string $player): array{
        $res = $mysqli->query("SELECT * FROM reports WHERE bad_player='$player' AND state='".ReportProvider::PROCESSED."'");
        if($res->num_rows <= 0) return [];
        $reports = [];

        while($data = $res->fetch_assoc()) $reports[$data["id"]] = $data;


        return $reports;
    }

    /**
     * @param mysqli $mysqli
     * @param int $state
     * @param string $bad_player
     */
    public static function setState(mysqli $mysqli, int $state, string $bad_player){
       $mysqli->query("UPDATE reports SET state='$state' WHERE bad_player='$bad_player' AND state!='".ReportProvider::PROCESSED."'");
    }

    /**
     * @param mysqli $mysqli
     * @param int $result
     * @param string $bad_player
     */
    public static function setResult(mysqli $mysqli, int $result, string $bad_player){
       $mysqli->query("UPDATE reports SET result='$result' WHERE bad_player='$bad_player' AND state!='".ReportProvider::PROCESSED."'");
    }

    /**
     * @param int $state
     * @return string
     */
    public static function stateToString(int $state): string{
        return match ($state) {
            self::OPEN => TextFormat::GREEN."OPEN",
            self::PROCESS => TextFormat::YELLOW."PROCESS",
            self::PROCESSED => TextFormat::RED."PROCESSED",
            self::ACCEPTED => TextFormat::GREEN."ACCEPTED",
            self::REJECTED => TextFormat::RED."REJECTED",
        };
    }
}