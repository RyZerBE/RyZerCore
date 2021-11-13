<?php

namespace ryzerbe\core\form\types;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ryzerbe\core\player\RyZerPlayerProvider;

class PlayerSettingsForm {

    /**
     * @param Player $player
     */
    public static function onOpen(Player $player){
        $rbePlayer = RyZerPlayerProvider::getRyzerPlayer($player);
        if($rbePlayer === null) return;
        $settings = $rbePlayer->getPlayerSettings();
        $color = function(bool $enabled): string{
            return match ($enabled) {
                true => TextFormat::GREEN,
                false => TextFormat::RED
            };
        };

        $form = new CustomForm(function(Player $player, $data) use ($rbePlayer): void{
            if($data === null) return;

            $more_particle = $data["more_particle"];
            $party_invites = $data["party_invites"];
            $friend_requests = $data["friend_requests"];
            $msg_toggle = $data["msg_toggle"];
            $rankToggle = $data["rank_toggle"];

            $rbePlayer->getPlayerSettings()->setMoreParticle($more_particle);
            $rbePlayer->getPlayerSettings()->setPartyInvitesEnabled($party_invites);
            $rbePlayer->getPlayerSettings()->setFriendRequestsEnabled($friend_requests);
            $rbePlayer->getPlayerSettings()->setMsgEnabled($msg_toggle);
            $rbePlayer->getPlayerSettings()->setToggleRank($rankToggle);
            $player->playSound("random.levelup", 5.0, 1.0, [$player]);
        });

        $form->setTitle(TextFormat::RED."Settings");
        $form->addToggle($color($settings->isMoreParticleActivated())."More Particle", $settings->isMoreParticleActivated(), "more_particle");
        $form->addToggle($color($settings->isPartyInvitesEnabled())."Party Invites", $settings->isPartyInvitesEnabled(), "party_invites");
        $form->addToggle($color($settings->isFriendRequestsEnabled())."Friend Requests", $settings->isFriendRequestsEnabled(), "friend_requests");
        $form->addToggle($color($settings->isMsgEnabled())."Private Messages", $settings->isMsgEnabled(), "msg_toggle");
        if($player->hasPermission("ryzer.togglerank")) {
            $form->addToggle($color($settings->isRankToggled())."Hide your rank", $settings->isRankToggled(), "rank_toggle");
        }

        $form->sendToPlayer($player);
    }
}