<?php


namespace baubolp\core\form\clan;


use BauboLP\Cloud\CloudBridge;
use baubolp\core\Ryzer;
use pocketmine\form\CustomForm;
use pocketmine\form\CustomFormResponse;
use pocketmine\form\element\Input;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class InvitePlayerForm extends CustomForm
{

    public function __construct()
    {
        $elements = [new Input("Invite", TextFormat::RED."Name of Player", "Steve", "")];
        parent::__construct(Ryzer::PREFIX.TextFormat::RED."Clans", $elements, function (Player $player, CustomFormResponse $response): void {
            $e = $this->getElement(0);
            $playerName = $response->getString($e->getName());
            if(strlen($playerName) > 16) {
                return;
            }

            CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan invite $playerName");
        });
    }
}