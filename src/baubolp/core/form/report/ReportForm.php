<?php


namespace baubolp\core\form\report;


use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\ReportProvider;
use baubolp\core\Ryzer;
use pocketmine\form\CustomForm;
use pocketmine\form\CustomFormResponse;
use pocketmine\form\element\Dropdown;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ReportForm extends CustomForm
{

    public function __construct()
    {
        $reasons = [];
        foreach (Ryzer::$banIds as $id => $data) {
            if($data['banreason'] != "Unerwünscht" && $data['banreason'] != "Betrugsversuch"
                && $data['banreason'] != "Sicherheitsban" && $data['banreason'] != "Betrugsversuch") {
                $reasons[] = $data['banreason'];
            }
        }
        $op = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $op[] = $player->getName();
        }
        $elements = [new Dropdown('Player', '', $op), new Dropdown('Reason', '', $reasons)];
        $onSubmit = function (Player $player, CustomFormResponse $response) use ($op, $reasons): void{
               $element1 = $this->getElement(0);
               $element2 = $this->getElement(1);
               if($element1 instanceof Dropdown && $element2 instanceof Dropdown) {
                   $badPlayer = $response->getInt($element1->getName());
                   $reason = $response->getInt($element2->getName());
                   $badPlayer = $op[$badPlayer];
                   $reason = $reasons[$reason];

                   if($badPlayer == $player->getName()) {
                       $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('cannot-report-self', $player->getName(), ['#playername' => $badPlayer]));
                       return;
                   }
                   if(ReportProvider::existReport($badPlayer)) {
                       $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('player-already-reported', $player->getName(), ['#playername' => $badPlayer]));
                       return;
                   }

                   ReportProvider::addReport($badPlayer, $player->getName(), $reason);
                   $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('successful-player-reported', $player->getName(), ['#playername' => $badPlayer, '#reason' => $reason]));

               }
        };
        parent::__construct(Ryzer::PREFIX.TextFormat::YELLOW."Reports", $elements, $onSubmit);
    }
}