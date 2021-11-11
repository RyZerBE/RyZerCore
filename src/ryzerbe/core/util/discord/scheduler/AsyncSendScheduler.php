<?php

namespace ryzerbe\core\util\discord\scheduler;

use pocketmine\scheduler\AsyncTask;
use ryzerbe\core\util\discord\DiscordMessage;

class AsyncSendScheduler extends AsyncTask {
    private DiscordMessage $discordMessage;

    public function __construct(DiscordMessage $discordMessage){
        $this->discordMessage = $discordMessage;
    }

    public function onRun(): void{
        DiscordMessage::sendMessage($this->discordMessage);
    }
}