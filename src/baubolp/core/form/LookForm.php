<?php


namespace baubolp\core\form;


use baubolp\core\provider\ModerationProvider;
use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class LookForm extends MenuForm
{

    public function __construct(Player $sender, string $rank, int $coins, string $verifyToken,
                                string $nick, string $language, string $ip, string $deviceId, string $deviceModel,
                                int $countOfReports, array $permissions, string $clan,
                                string $device, array $accounts, string $firstJoin, array $gamTime)
    {
        $options = [];
        $lookUp = "";
        $lookUp .= TextFormat::YELLOW."Rang: ".TextFormat::RED.$rank."\n";
        $lookUp .= TextFormat::YELLOW."Coins: ".TextFormat::RED.$coins."\n";
        $lookUp .= TextFormat::YELLOW."Verify-Token: ".TextFormat::RED.$verifyToken."\n";
        $lookUp .= TextFormat::YELLOW."Aktueller Nickname: ".TextFormat::RED.$nick."\n";
        $lookUp .= TextFormat::YELLOW."Sprache: ".TextFormat::RED.$language."\n";
        if($sender->hasPermission("core.look.ip"))
        $lookUp .= TextFormat::YELLOW."IP-Adresse: ".TextFormat::RED.$ip."\n";
        else
            $lookUp .= TextFormat::YELLOW."IP-Adresse: ".TextFormat::RED.ModerationProvider::hideAddress($ip)."\n";

        $lookUp .= TextFormat::YELLOW."Device: ".TextFormat::RED.$device.TextFormat::GRAY." (".TextFormat::YELLOW.$deviceModel.TextFormat::GRAY.")"."\n";
        $lookUp .= TextFormat::YELLOW."DeviceID: ".TextFormat::RED.$deviceId."\n";
        $lookUp .= TextFormat::YELLOW."Registrierung: ".TextFormat::RED.$firstJoin."\n";
        $lookUp .= TextFormat::YELLOW."Spielzeit: ".TextFormat::RED.$gamTime[0].TextFormat::YELLOW."H ".TextFormat::RED.$gamTime[1]."M"."\n";
        $lookUp .= TextFormat::YELLOW."Clan: ".TextFormat::RED.$clan."\n";
        $lookUp .= TextFormat::YELLOW."Verknüpfte Accounts: "."\n";
        foreach ($accounts as $account)
            $lookUp .= TextFormat::GRAY."- ".TextFormat::RED.$account."\n";

        $lookUp .= TextFormat::YELLOW."Rechte: \n";

        foreach ($permissions as $permission)
            $lookUp .= TextFormat::GRAY."- ".TextFormat::RED.$permission."\n";


        $lookUp .= "\n".TextFormat::YELLOW."Dieser Account wurde insgesamt ".TextFormat::RED.$countOfReports."x".TextFormat::YELLOW." gemeldet.";
        parent::__construct(Ryzer::PREFIX."Informations", $lookUp, $options, function (Player $player, int $selectedOption): void{}, function (Player $player):void {
            $player->sendMessage(Ryzer::PREFIX.TextFormat::RED.TextFormat::BOLD."Bitte behandel diese Informationen vertraulich! Es könnten auch Deine sein..");
            $player->playSound('note.bass', 5.0, 1.0, [$player]);
        });
    }
}