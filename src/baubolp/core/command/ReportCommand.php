<?php


namespace baubolp\core\command;


use baubolp\core\form\report\OverviewReportsForm;
use baubolp\core\form\report\ReportForm;
use baubolp\core\provider\StaffProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ReportCommand extends Command
{

    public function __construct()
    {
        parent::__construct('report', "report a cheater", "", ['melden']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;

        if($sender->hasPermission("core.reports") && StaffProvider::isLogin($sender->getName())) {
            $sender->sendForm(new OverviewReportsForm());
            return;
        }

        $sender->sendForm(new ReportForm());
    }
}