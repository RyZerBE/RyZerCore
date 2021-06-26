<?php


namespace baubolp\core\entity;


use baubolp\core\listener\own\TNTLightEvent;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Solid;
use pocketmine\entity\Entity;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;

class TNT extends Solid
{

    protected $id = self::TNT;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getName() : string{
        return "TNT";
    }

    public function getHardness() : float{
        return 0;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($item instanceof FlintSteel or $item->hasEnchantment(Enchantment::FIRE_ASPECT)){
            if($item instanceof Durable){
                $item->applyDamage(1);
            }
            $this->ignite();
            return true;
        }

        return false;
    }

    public function hasEntityCollision() : bool{
        return true;
    }

    public function onEntityCollide(Entity $entity) : void{
        /*if($entity instanceof Arrow and $entity->isOnFire()){
            $this->ignite();
        }*/
    }

    /**
     * @param int $fuse
     * @param int $team
     * @param int $radius
     * @return void
     */
    public function ignite(int $fuse = 80, int $team = 8, int $radius = 5){
        $this->getLevelNonNull()->setBlock($this, BlockFactory::get(Block::AIR), true);

        $mot = (new Random())->nextSignedFloat() * M_PI * 2;
        $nbt = Entity::createBaseNBT($this->add(0.5, 0, 0.5), new Vector3(-sin($mot) * 0.02, 0.2, -cos($mot) * 0.02));
        $nbt->setShort("Fuse", $fuse);
        $nbt->setString("Team", $team);
        $nbt->setString("Radius", $radius);

        $tnt = Entity::createEntity("PrimedTNT", $this->getLevelNonNull(), $nbt);

        if($tnt !== null){
            $ev = new TNTLightEvent($tnt, $fuse, $team, $radius);
            $ev->call();
            if(!$ev->isCancelled())
            $tnt->spawnToAll();
        }
    }

    public function getFlameEncouragement() : int{
        return 15;
    }

    public function getFlammability() : int{
        return 100;
    }

    public function onIncinerate() : void{
        //$this->ignite();
    }
}