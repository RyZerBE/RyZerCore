<?php


namespace baubolp\core\form\report;


use baubolp\core\provider\ModerationProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ArchiveInformationForm extends MenuForm
{

    public function __construct(array $data)
    {
        $accepted = ($data['accepted'] == true || $data['accepted'] == "true") ? TextFormat::GREEN."Ja" : TextFormat::RED."Nein";
        $deviceId = (isset($data['deviceId']) == true) ? $data['deviceId'] : TextFormat::RED."/";
        $ip = (isset($data['ip']) == true) ? $data['ip'] : TextFormat::RED."/";
        $text = TextFormat::YELLOW."Gemeldeter Spieler: ".TextFormat::AQUA.$data['badPlayer']."\n".
        TextFormat::YELLOW."Melder: ".TextFormat::AQUA.$data['sender']."\n".
        TextFormat::YELLOW."Grund: ".TextFormat::AQUA.$data['reason']."\n".
        TextFormat::YELLOW."Datum: ".TextFormat::AQUA.ModerationProvider::formatGermanDate($data['time'])."\n".
        TextFormat::YELLOW."Server: ".TextFormat::AQUA.$data['server']."\n".
        TextFormat::YELLOW."IP: ".TextFormat::AQUA.ModerationProvider::hideAddress($ip)."\n".
        TextFormat::YELLOW."DeviceID: ".TextFormat::AQUA.$deviceId."\n".
        TextFormat::YELLOW."Bearbeiter: ".TextFormat::AQUA.$data['staff']."\n".
        TextFormat::YELLOW."Angenommen: ".$accepted;
        parent::__construct(Ryzer::PREFIX.TextFormat::DARK_GREEN."Archivedatei auslesen", $text, [], function (Player $player, int $selectedOption): void {

        });
    }
}