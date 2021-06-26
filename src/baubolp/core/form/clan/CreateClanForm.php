<?php


namespace baubolp\core\form\clan;


use BauboLP\Cloud\CloudBridge;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use pocketmine\form\CustomForm;
use pocketmine\form\CustomFormResponse;
use pocketmine\form\element\Input;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CreateClanForm extends CustomForm
{

    public function __construct()
    {
        $elements = [new Input("ClanName", TextFormat::RED."Clan Name", "RyZerBE", ""), new Input("ClanTag", TextFormat::RED."Clan Tag", "RBE", "")];
        parent::__construct(Ryzer::PREFIX.TextFormat::RED."Clans", $elements, function (Player $player, CustomFormResponse $response): void{
            $e1 = $this->getElement(0);
            $e2 = $this->getElement(1);

            $clanName = $response->getString($e1->getName());
            $clanTag = $response->getString($e2->getName());
            if(str_replace(" ", "", $clanName) == "" || str_replace(" ", "", $clanTag) == "") {
                $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('something-went-wrong', $player->getName()));
                return;
            }


            if(is_string($clanName) && is_string($clanTag)) {
                if(strlen($clanTag) > 5) {
                    $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('clantag-too-long', $player->getName()));
                    return;
                }

                CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan create $clanName $clanTag");
            }else {
                $player->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('something-went-wrong', $player->getName()));
            }
        });
    }
}