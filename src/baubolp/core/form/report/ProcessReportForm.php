<?php


namespace baubolp\core\form\report;


use BauboLP\Cloud\Bungee\BungeeAPI;
use baubolp\core\provider\CoinProvider;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\ModerationProvider;
use baubolp\core\provider\ReportProvider;
use baubolp\core\provider\StaffProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ProcessReportForm extends MenuForm
{

    public function __construct(string $badPlayer)
    {
        $buttons = [TextFormat::YELLOW.'Bearbeiten', TextFormat::GREEN."Annehmen", TextFormat::RED."Ablehnen"];
        $options = [];

        $reportData = ReportProvider::getReportInformation($badPlayer);

        foreach ($buttons as $button) {
            $options[] = new MenuOption($button);
        }

        $onSubmit = function (Player $player, int $selectedOption) use ($buttons, $reportData, $badPlayer): void{
                $button = TextFormat::clean($buttons[$selectedOption]);

                switch ($button) {
                    case "Bearbeiten":
                        //TODO: Connecten zum Server wo der BadPlayer ist.

                        BungeeAPI::sendMessage(LanguageProvider::getMessageContainer('report-process', $reportData['sender'], ['#badplayer' => $badPlayer]), $reportData['sender']);
                        StaffProvider::sendMessageToStaffs(TextFormat::AQUA.$player->getName().TextFormat::GRAY." bearbeitet nun den Fall ".TextFormat::RED.$badPlayer.TextFormat::GRAY.".");
                        ModerationProvider::broadcastMessageToStaffs(Ryzer::PREFIX.TextFormat::AQUA.$player->getName().TextFormat::GRAY." bearbeitet nun den Fall ".TextFormat::RED.$badPlayer.TextFormat::GRAY.".");
                        break;
                    case "Annehmen":
                        ReportProvider::addReportToArchiv($badPlayer, $reportData['sender'], $reportData['reason'], true, $player->getName(), $reportData['server'], $reportData['deviceId'], $reportData['ip']);
                        ReportProvider::removeReport($badPlayer);
                        BungeeAPI::sendMessage(LanguageProvider::getMessageContainer('report-accepted', $reportData['sender'], ['#playername' => $badPlayer]), $reportData['sender']);
                        CoinProvider::addCoins($reportData['sender'], 80);
                        ModerationProvider::broadcastMessageToStaffs(Ryzer::PREFIX.TextFormat::AQUA.$player->getName().TextFormat::GRAY." hat den Fall ".TextFormat::RED.$badPlayer.TextFormat::GREEN." angenommen".TextFormat::GRAY.".");
                        break;
                    case "Ablehnen":
                        ReportProvider::addReportToArchiv($badPlayer, $reportData['sender'], $reportData['reason'], false, $player->getName(), $reportData['server'], $reportData['deviceId'], $reportData['ip']);
                        ReportProvider::removeReport($badPlayer);
                        BungeeAPI::sendMessage(LanguageProvider::getMessageContainer('report-decline', $reportData['sender'], ['#playername' => $badPlayer]), $reportData['sender']);
                        ModerationProvider::broadcastMessageToStaffs(Ryzer::PREFIX.TextFormat::AQUA.$player->getName().TextFormat::GRAY." hat den Fall ".TextFormat::RED.$badPlayer.TextFormat::RED." abgelehnt".TextFormat::GRAY.".");
                        break;
                }
        };
        $deviceId = (isset($reportData['deviceId']) == true) ? $reportData['deviceId'] : TextFormat::RED."/";
        $ip = (isset($reportData['ip']) == true) ? $reportData['ip'] : TextFormat::RED."/";
        $text = TextFormat::YELLOW."Gemeldeter Spieler: ".TextFormat::AQUA.$badPlayer."\n".
                TextFormat::YELLOW."Melder: ".TextFormat::AQUA.$reportData['sender']."\n".
                TextFormat::YELLOW."Grund: ".TextFormat::AQUA.$reportData['reason']."\n".
                TextFormat::YELLOW."Datum: ".TextFormat::AQUA.ModerationProvider::formatGermanDate($reportData['time'])."\n".
                TextFormat::YELLOW."Server: ".TextFormat::AQUA.$reportData['server']."\n".
                TextFormat::YELLOW."IP: ".TextFormat::AQUA.ModerationProvider::hideAddress($ip)."\n".
                TextFormat::YELLOW."DeviceID: ".TextFormat::AQUA.$deviceId;
        parent::__construct(Ryzer::PREFIX.TextFormat::YELLOW."Process", $text, $options, $onSubmit);
    }
}