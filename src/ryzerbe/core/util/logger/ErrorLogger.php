<?php
declare(strict_types=1);
namespace ryzerbe\core\util\logger;


use BauboLP\Cloud\Bungee\BungeeAPI;
use BauboLP\Cloud\Provider\CloudProvider;
use DateTime;
use LogLevel;
use pocketmine\utils\TextFormat;
use ryzerbe\core\util\discord\color\DiscordColor;
use ryzerbe\core\util\discord\DiscordMessage;
use ryzerbe\core\util\discord\embed\DiscordEmbed;
use ryzerbe\core\util\discord\embed\options\EmbedAuthor;
use ryzerbe\core\util\discord\WebhookLinks;
use ThreadedLoggerAttachment;
use function str_contains;

class ErrorLogger extends ThreadedLoggerAttachment{

	const INFO = [
		"BauboLPYT",
		"Matze998",
		"zuWxld"
	]; //TODO: Do a team setting for that! - will come with team setting update (per ui)

	public static int|float $lastCall = -1;

	/**
	 * Function log
	 * @param mixed $level
	 * @param string $message
	 * @return void
	 */
	public function log($level, $message){
		switch ($level) {
			case LogLevel::ERROR:
			case LogLevel::CRITICAL:
			case LogLevel::EMERGENCY:
			    if(str_contains($message, " > #")) return;
				$discordMessage = new DiscordMessage(WebhookLinks::ERROR_LOGGER);
				$embed = new DiscordEmbed();
				$embed->setTitle($level . ": " . TextFormat::clean($message));
				$embed->setDateTime(new DateTime());
				$embed->setColor(DiscordColor::RED);
				$embed->setFooter(CloudProvider::getServer());
				$embed->setAuthor(new EmbedAuthor(CloudProvider::getServer()));
				$discordMessage->addEmbed($embed);
				$discordMessage->send();
				if(self::$lastCall > microtime(true)) return;

				foreach (self::INFO as $infoPlayerName) {
					BungeeAPI::sendMessage(TextFormat::GRAY . "[" . TextFormat::RED . "Error-Logger" . TextFormat::GRAY . "] " . TextFormat::YELLOW . CloudProvider::getServer() . TextFormat::GRAY . " hat einen Fehler gespeichert.", $infoPlayerName);
				}
				self::$lastCall = microtime(true) + 5;
				break;
		}
	}
}
