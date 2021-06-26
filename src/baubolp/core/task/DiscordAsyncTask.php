<?php


namespace baubolp\core\task;


use pocketmine\scheduler\AsyncTask;

class DiscordAsyncTask extends AsyncTask
{

    private $webhook, $curlopts, $embed;

    public function __construct($webhook, $curlopts, $embed = false)
    {
        $this->webhook = $webhook;
        $this->curlopts = $curlopts;
        $this->embed = $embed;
    }

    public function onRun(): void
    {
        if($this->embed) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->webhook);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->curlopts);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Length: " . strlen($this->curlopts)));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
            $curlerror = curl_error($curl);
            $responsejson = json_decode($response, true);
            $success = false;
            $error = 'Discord Webhook ERROR!';
            if ($curlerror != '') {
                $error = $curlerror;
            } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204) {
                // $error = $responsejson['message'];
                $error = curl_error($curl);
            } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 204 OR $response === '') {
                $success = true;
            }
            $result = ['Response' => $response, 'Error' => $error, 'success' => $success];
            #var_dump($result);
            $this->setResult($result);
            return;
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->webhook);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(unserialize($this->curlopts)));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        $curlerror = curl_error($curl);
        $responsejson = json_decode($response, true);

        $success = false;
        $error = 'Discord Webhook ERROR!';

        if ($curlerror != '') {
            $error = $curlerror;
        } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204) {
            $error = $responsejson['message'];
        } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 204 OR $response === '') {
            $success = true;
        }

        $result = ['Response' => $response, 'Error' => $error, 'success' => $success];
        $this->setResult($result);
    }
}