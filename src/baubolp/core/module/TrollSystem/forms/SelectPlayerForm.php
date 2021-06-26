<?php


namespace baubolp\core\module\TrollSystem\forms;


use BauboLP\Cloud\Bungee\BungeeAPI;
use BauboLP\Cloud\CloudBridge;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\Ryzer;
use baubolp\core\module\TrollSystem\TrollSystem;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SelectPlayerForm extends MenuForm
{

    public function __construct(string $action)
    {
        $options = [];
        $players = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $players[] = $player->getName();
            $options[] = new MenuOption($player->getDisplayName());
        }
        parent::__construct(TrollSystem::Prefix.TextFormat::YELLOW."Select Player", "", $options, function (Player $player, int $selectedOption) use ($players, $action):void{
                $playerName = $players[$selectedOption];

                if(($opfer = Server::getInstance()->getPlayerExact($playerName)) != null) {
                    switch ($action) {
                        case "Cloud Error":
                            $array = [
                                "Internal Server Error",
                                "Argument 1 passed to pocketmine/entity/Entity::createBaseNBT() must be an instance of pocketmine\math\Vector3, string given",
                                "Argument 2 passed to pocketmine\math\AxisAlignedBB::setBounds() must be of the type float, string given",
                                "syntax error, unexpected ';', expecting ')'",
                                "Array to string conversion",
                                "Undefined offset: 1",
                                "explode() expects parameter 2 to be string, bool given",
                                "Division by zero",
                                "Unable to read 4 bytes from HexIntStringBinaryArrayBufferAttributeChunkHashList, Parameter 137 needs to be instance of Mainframe",
                                "System Shutdown: Mainframe Overloaded: Expected MaxInt<265>, KillerByte<> found",
                                "RAM Overloaded: Can we use yours?",
                                "Unexpected int 0x0231c3 at position 3 of ByteBufIndexArrayLengthArgumentPointingCalculator.cpp",
                                "Unexpected var^&char, expecting more advanced knowledge about this framework",
                                "Folder /EntenGames/Lagfrei/ seems to be empty. Conincidence?",
                                "File /SystemLukas/Arbeit not found. Is he dead?",
                                "RyzerCloud Thread Shutdown",
                                "Client connection lost",
                                "Proxy connection lost",
                                "NinjaHub date of release not found. Try it again in 3 years",
                                "Packets out of order. Expected 0 received 1. Packet size=23",
                                "BotNet of RushNation failed."];
                            $index = rand(1, count($array)) - 1;
                            $opfer->sendMessage(CloudBridge::Prefix.TextFormat::RED.$array[$index]);
                            BungeeAPI::transfer($opfer->getName(), Ryzer::getRandomLobby());
                            $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-cloud-error', $player->getName()));
                            break;
                        case "AntiCheat Kick":
                            $messages = [
                                "Your click speed is too high! Clicks: 71 CPS",
                                "Flying is not allowed on this server",
                                "Hacking is not allowed bro",
                                "Please contact our staff team. You are mysterious..",
                                "Spotify Client detected!",
                                "Toolbox detected!"
                            ];
                            $index = rand(1, count($messages)) - 1;
                            $opfer->sendMessage(TextFormat::BOLD.TextFormat::DARK_AQUA."AntiCheat ".TextFormat::RESET.TextFormat::RED.$messages[$index]);
                            $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-anticheat-kick', $player->getName()));
                            BungeeAPI::transfer($opfer->getName(), Ryzer::getRandomLobby());
                            break;
                        case "MLG Player":
                            $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-mlg-player', $player->getName()));
                            $item = Item::get(Item::COBWEB, 0, 1)->setCustomName(TextFormat::RED . "Make a MLG!");
                            $opfer->getInventory()->setItem(0, $item);
                            $opfer->setMotion(new Vector3(0, 7, 0));
                            break;
                        case "Freeze Player":
                            if($opfer->isImmobile()) {
                                $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-unfreezed', $player->getName()));
                                $opfer->setImmobile(false);
                            }else {
                                $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-freezed', $player->getName()));
                                $opfer->setImmobile();
                            }
                            break;
                        case "Drop Items":
                            $item = $player->getInventory()->getItemInHand();
                            $opfer->dropItem($item);
                            $opfer->getInventory()->setItemInHand(Item::get(Item::AIR));
                            break;
                        case "Crash Player":
                            if(!$player->hasPermission("troll.crash")) {
                                $player->sendMessage(TrollSystem::Prefix.TextFormat::DARK_RED."You don't have permission to use the crash action! Only the team members can use this troll feature!");
                                return;
                            }

                            $chunk = $opfer->getLevel()->getChunkAtPosition($opfer);
                            $pk = LevelChunkPacket::withCache($chunk->getX(), $chunk->getZ(), 100000, [], "");
                            $opfer->sendDataPacket($pk);
                            $player->sendMessage(TrollSystem::Prefix . "The MC of the player crash now...");
                            break;
                        case "AntiDrop":
                            if(!in_array($opfer->getName(), Ryzer::getTrollSystem()->antiDrop)) {
                                $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-antidrop', $player->getName()));
                                Ryzer::getTrollSystem()->antiDrop[] = $opfer->getName();
                            }else{
                                $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-can-now-drop', $player->getName()));
                                unset(Ryzer::getTrollSystem()->antiDrop[array_search($opfer->getName(), Ryzer::getTrollSystem()->antiDrop)]);
                            }
                            break;
                        case "Alone":
                            if(!in_array($opfer->getName(), Ryzer::getTrollSystem()->alone)) {
                                $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-alone', $player->getName()));
                                Ryzer::getTrollSystem()->alone[] = $opfer->getName();
                                foreach (Server::getInstance()->getOnlinePlayers() as $oPlayers) {
                                    $opfer->hidePlayer($oPlayers);
                                }
                            }else{
                                $player->sendMessage(TrollSystem::Prefix.LanguageProvider::getMessageContainer('troll-no-longer-alone', $player->getName()));
                                unset(Ryzer::getTrollSystem()->alone[array_search($opfer->getName(), Ryzer::getTrollSystem()->alone)]);
                                foreach (Server::getInstance()->getOnlinePlayers() as $oPlayers) {
                                    $opfer->showPlayer($oPlayers);
                                }
                            }
                            break;
                    }
                }
        });
    }

}