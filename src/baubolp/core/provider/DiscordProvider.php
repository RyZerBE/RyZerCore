<?php


namespace baubolp\core\provider;


use BauboLP\Cloud\Bungee\Protocol\BufferFactory;
use BauboLP\Cloud\Bungee\Protocol\Request;
use BauboLP\Cloud\Bungee\Protocol\RequestPool;
use BauboLP\Cloud\Bungee\Protocol\RequestType;
use BauboLP\Cloud\Provider\CloudProvider;
use baubolp\core\Ryzer;
use baubolp\core\task\DiscordAsyncTask;
use baubolp\core\util\Webhooks;
use pocketmine\Server;

class DiscordProvider
{

    /**
     * @param string $username
     * @param string $message
     * @param $webhook
     */
    public static function sendMessageToDiscord(string $username, string $message, $webhook)
    {
       // $message = str_replace("@", "", $message);
        $curlopts = [
            'content' => $message,
            'username' => $username
        ];

        Ryzer::getPlugin()->getServer()->getAsyncPool()->submitTask(new DiscordAsyncTask($webhook, serialize($curlopts)));
    }

    /**
     * @param $webhook - Webhook URL
     * @param string $embed_name - Webhook name
     * @param array $fields - [name => String, "value" => String, inline => boolean]
     * @param array $footer - ["text" => String, icon_url => URL(String)]
     * @param $color - RGB Code z. B https://convertingcolors.com/decimal-color-16253719.html
     * @param String $title - Title of Embed
     * @param string $description - Description of the embed
     */
    public static function sendEmbedMessageToDiscord($webhook, string $embed_name, array $fields, array $footer, $color, String $title, $description = "")
    {
        $embedObject = json_encode( [
            "content" => "",
            "username" => $embed_name,
            "avatar_url" => "",
            "tts" => false,
            "embeds" => [
                [
                    "title" => $title,
                    "type" => "rich",
                    "description" => $description,
                    "color" => ($color == null) ? 16312092 : $color,
                    "footer" => $footer,
                    "fields" => $fields,
                ],
            ],

        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        Ryzer::getPlugin()->getServer()->getAsyncPool()->submitTask(new DiscordAsyncTask($webhook, $embedObject, true));
    }

    /**
     * @param string $username
     * @param $firstcheck
     * @param $secondcheck
     * @param $thirdcheck
     * @param $average
     * @param $importance
     * @param $calls
     * @param $device
     */
    public static function reportAC(string $username, $firstcheck, $secondcheck, $thirdcheck, $average, $importance, $calls, $device)
    {
        if(($player = Server::getInstance()->getPlayerExact($username)) != null) {
            $ping = "unknown";
            $tps = Server::getInstance()->getTicksPerSecond();
            $tps_procent = Server::getInstance()->getTickUsage();

            $importance = str_replace("§6", "", $importance);
            $importance = str_replace("§4", "", $importance);
            $importance = str_replace("§c", "", $importance);
            DiscordProvider::sendEmbedMessageToDiscord(Webhooks::AC_LOG, "Spion", [
                ['name' => "Detected", 'value' => $username, 'inline' => false]
                , ['name' => "Device", 'value' => $device, 'inline' => false]
                , ['name' => "Firstcheck", 'value' => $firstcheck . " Clicks", 'inline' => false]
                , ['name' => "Secondcheck", 'value' => $secondcheck . " Clicks", 'inline' => false]
                , ['name' => "Thirdcheck", 'value' => $thirdcheck . " Clicks", 'inline' => false]
                , ['name' => "AverageCPS(last 3sec)", 'value' => $average . " Clicks", 'inline' => false]
                , ['name' => "Importance(1-3)", 'value' => $importance, 'inline' => false]
                , ['name' => "Warning Calls", 'value' => $calls . "x", 'inline' => false]
                , ['name' => "Ping", 'value' => $ping . "ms", 'inline' => false]
                , ['name' => "Server", 'value' => CloudProvider::getServer(), 'inline' => false]
                , ['name' => "TPS", 'value' => $tps . "($tps_procent%)", 'inline' => false]], ["text" => "RyZerBE", "icon_url" => Webhooks::ICON], null, "AutoClicker Verdacht");

        }
    }
}