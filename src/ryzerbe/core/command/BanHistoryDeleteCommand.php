<?php

namespace ryzerbe\core\command;

use DateTime;
use mysqli;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\WebhookLinks;
use ryzerbe\core\util\embed\DiscordEmbed;
use ryzerbe\core\util\embed\options\EmbedField;

class BanHistoryDeleteCommand extends Command {

    public function __construct()
    {
        parent::__construct("banentryreset", "remove punish individually entries or all entries of a player", "", ["banhistoryreset"]);
        $this->setPermission("ryzer.banentryreset");
        $this->setPermissionMessage(RyZerBE::PREFIX.TextFormat::RED."No Permissions, im sorry :c");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)) return;
        if(empty($args[0])) {
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::YELLOW."/banentryreset <PlayerName> - Entferne alle Baneinträge eines Spielers");
            $sender->sendMessage(RyZerBE::PREFIX.TextFormat::YELLOW."/banentryreset <EntryId> - Entferne einen einzelnen Eintrag");
            return;
        }

        if(is_numeric($args[0])) {
            $entryId = $args[0];
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function (mysqli $mysqli) use ($entryId){
                $mysqli->query("DELETE FROM `punishments` WHERE id='$entryId'");
                if(count($mysqli->error_list) > 0) return false;

                return true;
            }, function (Server $server, bool $success) use ($sender, $entryId){
                if($sender === null) return;

                if($success) {
                    $discordMessage = new DiscordMessage(WebhookLinks::PUNISHMENT_LOG);
                    $discordEmbed = new DiscordEmbed();
                    $discordEmbed->setTitle("#".$entryId." wurde gelöscht");
                    $discordEmbed->setColor(DiscordColor::RED);
                    $discordEmbed->setFooter("RyZerBE Moderation", "https://media.discordapp.net/attachments/602115215307309066/907944961037729792/rbe_logo_new.png?width=702&height=702");
                    $discordEmbed->setThumbnail("https://media.discordapp.net/attachments/602115215307309066/907974343227752538/2132336.png?width=410&height=410");
                    $discordEmbed->addField(new EmbedField(":no_entry: EntryID", $entryId, true));
                    $discordEmbed->addField(new EmbedField(":cop: Moderator", $sender->getName(), true));
                    $discordEmbed->setDateTime(new DateTime());
                    $discordMessage->addEmbed($discordEmbed);
                    $discordMessage->send();
                    $sender->sendMessage(RyZerBE::PREFIX.TextFormat::GRAY."Der Eintrag wurde ".TextFormat::GREEN."erfolgreich ".TextFormat::RED."entfernt".TextFormat::GRAY.".");
                } else {
                    $sender->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Der Eintrag existiert nicht!");
                }
            });
        }else {
            $playerName = $args[0];
            AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function (mysqli $mysqli) use ($playerName) {
                $mysqli->query("DELETE FROM `punishments` WHERE player='$playerName'");
                if (count($mysqli->error_list) > 0) return false;

                return true;
            }, function (Server $server, bool $success) use ($sender, $playerName) {
                if ($sender === null) return;

                if ($success) {
                    $discordMessage = new DiscordMessage(WebhookLinks::PUNISHMENT_LOG);
                    $discordEmbed = new DiscordEmbed();
                    $discordEmbed->setTitle("Einträge von ".$playerName." wurden gelöscht");
                    $discordEmbed->setColor(DiscordColor::RED);
                    $discordEmbed->setFooter("RyZerBE Moderation", "https://media.discordapp.net/attachments/602115215307309066/907944961037729792/rbe_logo_new.png?width=702&height=702");
                    $discordEmbed->setThumbnail("https://media.discordapp.net/attachments/602115215307309066/907974343227752538/2132336.png?width=410&height=410");
                    $discordEmbed->addField(new EmbedField(":no_entry: Entries of ", $playerName, true));
                    $discordEmbed->addField(new EmbedField(":cop: Moderator", $sender->getName(), true));
                    $discordEmbed->setDateTime(new DateTime());
                    $discordMessage->addEmbed($discordEmbed);
                    $discordMessage->send();
                    $sender->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Die Einträge des Spielers wurden " . TextFormat::GREEN . "erfolgreich " . TextFormat::RED . "entfernt" . TextFormat::GRAY . ".");
                } else {
                    $sender->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Der Spieler hat keine Einträge!");
                }
            });
        }
    }
}