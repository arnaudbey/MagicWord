<?php

namespace  MagicWordBundle\Manager;

use Symfony\Component\HttpFoundation\Request;
use JMS\DiExtraBundle\Annotation as DI;
use MagicWordBundle\Entity\Game;
use MagicWordBundle\Entity\Grid;
use MagicWordBundle\Entity\Round;
use MagicWordBundle\Entity\RoundType\Rush;
use MagicWordBundle\Entity\RoundType\Conquer;

/**
 * @DI\Service("mw_manager.round")
 */
class RoundManager
{
    protected $em;
    protected $gridManager;

    /**
     * @DI\InjectParams({
     *      "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     *      "gridManager"   = @DI\Inject("mw_manager.grid"),
     * })
     */
    public function __construct($entityManager, $gridManager)
    {
        $this->em = $entityManager;
        $this->gridManager = $gridManager;
    }

    public function getData(Round $round)
    {
        $grid = $round->getGrid();
        $data = array();
        $data['inflections'] = json_encode($grid, JSON_PRETTY_PRINT);
        $data['objectives'] = json_encode($round, JSON_PRETTY_PRINT);

        return $data;
    }

    public function generateRush(Game $game, Grid $grid)
    {
        $round = new Rush();
        $round->setGame($game);
        $round->setGrid($grid);
        $round->setDisplayOrder($this->getNextDisplayOrder($game));
        $this->em->persist($round);
        $this->em->flush();

        return $round;
    }

    public function generateConquer(Game $game)
    {
        $round = new Conquer();
        $round->setGame($game);
        $round->setGrid(null);
        $round->setDisplayOrder($this->getNextDisplayOrder($game));
        $this->em->persist($round);
        $this->em->flush();

        return $round;
    }

    public function saveConquerGrid(Conquer $conquer, Request $request)
    {
        if (!$grid = $conquer->getGrid()) {
            $grid = $this->gridManager->createGrid($request, true);
            $conquer->setGrid($grid);
            $this->em->persist($conquer);
            $this->em->flush();
        } else {
            $this->gridManager->updateGrid($grid, $request);
        }
    }

    private function getNextDisplayOrder(Game $game)
    {
        return $game->getRounds()->count();
    }
}