<?php


namespace baubolp\core\form;


use baubolp\core\Ryzer;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ApplyDescriptionForm extends MenuForm
{

    const CONTENT = 0;
    const BUILDER = 1;
    const STAFF = 2;
    const DEVELOPER = 3;

    public function __construct(int $rank)
    {
        $options = [];
        if($rank == self::CONTENT) {
            $options[] = new MenuOption(TextFormat::DARK_AQUA."content.ryzer.be");
            $text = TextFormat::GRAY."";
        }else if($rank == self::BUILDER) {
            $options[] = new MenuOption(TextFormat::DARK_GREEN."builder.ryzer.be");
            $text = TextFormat::GRAY."Was macht eigentlich der Builder?\n".TextFormat::AQUA."Hmmm, ".TextFormat::GREEN."Gute Frage!\n".TextFormat::WHITE."Der Builder hat die Aufgabe, das Netzwerk mit gut gebauten Maps(z.B BedWars, SkyWars) zu untersützen."
            ."Natürlich baut der Builder nicht nur Maps für unsere Spielmodi, sondern übernimmt er auch größere Bauwerke. Zum Beispiel unsere richtig coole Lobby!\n\n".
            TextFormat::GREEN."Du denkst, du kannst gut bauen & unser Netzwerk aktiv unterstützen? Ja? Okay, dann schauen wir uns mal die Kriterien an:\n\n".
            TextFormat::RED."Das Lebensalter von 12 Jahren erreicht,\n".
            TextFormat::RED."Zuverlässigkeit,\n".
            TextFormat::RED."Freundlich,\n".
            TextFormat::RED."Hilfsbereit,\n".
            TextFormat::RED."Arbeitest gut mit anderen Buildern zusammen,\n".
            TextFormat::RED."In Besitz von Discord".TextFormat::DARK_GRAY."(".TextFormat::AQUA."discord.ryzer.be".TextFormat::DARK_GRAY."),\n".
            TextFormat::RED."Eine angenehme Mikrofon Qualität\n\n".
            TextFormat::AQUA."WOOOOOOOOW! ".TextFormat::GREEN."Du erfüllst alle Kriterien?\n".TextFormat::AQUA."Ja, dann mal los. ".TextFormat::GREEN."Bewerb dich jetzt unter:";
        }elseif($rank == self::STAFF) {
            $options[] = new MenuOption(TextFormat::RED."staff.ryzer.be");
            $text = TextFormat::GRAY."Welche Aufgaben übernimmt eigentlich ein ".TextFormat::RED."Staff".TextFormat::GRAY."? Und wie wirst Du ein ".TextFormat::RED."Staff".TextFormat::GRAY."?\n".
                    TextFormat::GREEN."Alles über den beliebten Job findest Du hier!\n\n".
                    TextFormat::WHITE."Ein Staff übernimmt im Großen und Ganzen die Moderation.\n Das heißt, er supportet Hilfesuchende Spieler und bestraft die User, welche Regelverstöße begehen.\n Auch auf unserem Discord ist der Staff eine wichtige Rolle. Auf dem Discord hilft er sogar sprachlich weiter und bearbeitet offene Tickets.\n".
                    "\nJedoch sollte ein Staff selbst nicht negativ auffallen. User wünschen sich ein kompetentes Serverteam.\n\n".
                    TextFormat::AQUA."Spannend oder? Genau Dein Ding? Los, schauen wir uns die Kriterien an:\n\n".
                    TextFormat::RED."Das Lebensalter von 14 Jahren erreicht\n".
                    TextFormat::RED."Zuverlässigkeit,\n".
                    TextFormat::RED."Freundlichkeit,\n".
                    TextFormat::RED."Hilfsbereit,\n".
                    TextFormat::RED."Sympathie,\n".
                    TextFormat::RED."Teamfähig,\n".
                    TextFormat::RED."Keine negativen Auffälligkeiten (z. B auf anderen Servern),\n".
                    TextFormat::RED."Viel Freizeit,\n".
                    TextFormat::RED."Eine gute Mikrofonqualität, denn man sollte dich gut im Voice Support verstehen\n\n".
                    TextFormat::YELLOW."So, Du hast noch keinen Nervenzusammenbruch erlitten? Dann bewerbe Dich doch gerne ;)\n".
                    TextFormat::RED."Denk bitte dran, dass wir nicht ständig Staffs suchen und vor allem nur begrenzt. Viel Erfolg!\n";
        }else {
            $options[] = new MenuOption(TextFormat::AQUA."developer.ryzer.be");
            $text = TextFormat::GRAY."";
        }

        //TODO: Other Rank Descriptions
        parent::__construct(Ryzer::PREFIX.TextFormat::GREEN."Apply", $text, $options, function (Player $player, int $selectedOption): void{});
    }
}