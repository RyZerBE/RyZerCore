<?php
declare(strict_types=1);
namespace ryzerbe\core\command;


use DateTime;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ryzerbe\core\RyZerBE;
use ryzerbe\core\util\async\AsyncExecutor;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\embed\DiscordEmbed;
use ryzerbe\core\util\discord\embed\options\EmbedField;
use ryzerbe\core\util\discord\WebhookLinks;


class SetBanCommand extends Command{

	public function __construct(){
		parent::__construct("untilban", "edit punish until individually", "", []);
		$this->setPermission("ryzer.untilban");
		$this->setPermissionMessage(RyZerBE::PREFIX . TextFormat::RED . "No Permissions, im sorry :c");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;
		if (!$this->testPermission($sender))
			return;

		$form = new CustomForm(function (Player $player, $data): void{
			if($data === null) return;
			$banId = $data["id"];
			$newUntil = $data["time"];
			$newUntilTime = strtotime($newUntil);
			if($newUntilTime === false) {
				$player->sendMessage(RyZerBE::PREFIX.TextFormat::RED."Bitte überprüfe deine Zeitangabe! Etwas stimmt damit nicht...");
				return;
			}

			$newUntilTime = date("Y-m-d H:i:s", $newUntilTime);

			AsyncExecutor::submitMySQLAsyncTask("RyZerCore", function (\mysqli $mysqli) use ($banId, $newUntilTime): bool{
				$res = $mysqli->query("SELECT * FROM punishments WHERE id='$banId'");
				if($res->num_rows <= 0 || count($mysqli->error_list)) return false;
				$mysqli->query("UPDATE `punishments` SET until='$newUntilTime' WHERE id='$banId'");
				return true;
			}, function (Server $server, bool $success) use ($banId, $newUntilTime, $player): void{
				if($player === null) return;
				if($success){
					$discordMessage = new DiscordMessage(WebhookLinks::PUNISHMENT_LOG);
					$discordEmbed = new DiscordEmbed();
					$discordEmbed->setTitle("#" . $banId . " wurde verändert");
					$discordEmbed->setColor(DiscordColor::RED);
					$discordEmbed->setFooter("RyZerBE Moderation", "https://media.discordapp.net/attachments/602115215307309066/907944961037729792/rbe_logo_new.png?width=702&height=702");
					$discordEmbed->setThumbnail("https://media.discordapp.net/attachments/602115215307309066/907974343227752538/2132336.png?width=410&height=410");
					$discordEmbed->addField(new EmbedField(":no_entry: EntryID", $banId, true));
					$discordEmbed->addField(new EmbedField(":cop: Moderator", $player->getName(), true));
					$discordEmbed->addField(new EmbedField(":cop: New until", $newUntilTime, true));
					$discordEmbed->setDateTime(new DateTime());
					$discordMessage->addEmbed($discordEmbed);
					$discordMessage->send();
					$player->sendMessage(RyZerBE::PREFIX . TextFormat::GRAY . "Die Dauer der Bestrafung (§e{$banId}§7) wurde §everändert§7. Der Spieler ist nun §ebis zum ".$newUntilTime." §7gebannt / gemutet.");
				}
				else{
					$player->sendMessage(RyZerBE::PREFIX . TextFormat::RED . "Der Eintrag existiert nicht!");
				}
			});
		});
		$form->addInput(TextFormat::YELLOW."EntryId", "871", "", "id");
		$form->addInput(TextFormat::YELLOW."Ende des Bans / Mutes", "12.04.2005 16:00", "", "time");
		$form->setTitle("Edit Untildate");
		$form->sendToPlayer($sender);
	}
}
