<?php


namespace baubolp\core\command;


use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\player\RyzerPlayerProvider;
use baubolp\core\provider\AsyncExecutor;
use baubolp\core\provider\LanguageProvider;
use baubolp\core\provider\NickProvider;
use baubolp\core\Ryzer;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class NickCommand extends Command
{

    public function __construct()
    {
        Ryzer::addPermission("core.nick");
        parent::__construct("nick", "hide your identity", "", []);
        $this->setPermission("core.nick");
        $this->setPermissionMessage(Ryzer::PREFIX.TextFormat::RED."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        if(isset($args[0])) {
            if($args[0] === "list" && $sender->hasPermission("core.nick.list")) {
                $senderName = $sender->getName();
                AsyncExecutor::submitMySQLAsyncTask("RyzerCore", function(\mysqli $mysqli): ?array{
                    $res = $mysqli->query("SELECT * FROM Nick");
                    if($res->num_rows <= 0) return null;

                    $nicks = [];
                    while($data = $res->fetch_assoc()) {
                        $nicks[$data["playername"]] = $data["nick"];
                    }

                    return $nicks;
                }, function(Server $server, ?array $nicks) use ($senderName){
                    $player = $server->getPlayerExact($senderName);
                    if($player === null) return;
                    if($nicks === null) {
                        $player->sendMessage(Ryzer::PREFIX.TextFormat::RED."Aktuell hat niemand eine gefälschte Identität.");
                    }else {
                        $form = new SimpleForm(function(Player $player, $data): void{});
                        $form->setTitle(TextFormat::GOLD."List of active nicks");
                        foreach($nicks as $playerName => $nickName) {
                            $form->addButton(TextFormat::GREEN.$nickName."\n".TextFormat::DARK_GRAY."(".TextFormat::GOLD.$playerName.TextFormat::DARK_GRAY.")");
                        }
                        $form->sendToPlayer($player);
                    }
                });
            }
            return;
        }

        if(stripos(CloudProvider::getServer(), "CWBW") !== false) {
            $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('cannot-nick-in-cwbw', $sender->getName()));
            return;
        }

        if(($obj = RyzerPlayerProvider::getRyzerPlayer($sender->getName())) != null) {
            if($obj->getNick() == null) {
                Ryzer::getNickProvider()->setNick($sender, $obj);
                $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('nick-set', $sender->getName()));
                Ryzer::getNickProvider()->showNickToTeam($sender);
            }else {
                Ryzer::getNickProvider()->removeNick($sender, $obj);
                $sender->sendMessage(Ryzer::PREFIX.LanguageProvider::getMessageContainer('nick-removed', $sender->getName()));
            }
        }
    }
}