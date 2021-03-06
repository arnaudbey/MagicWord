<?php

namespace  MagicWordBundle\Manager\GameType;

use JMS\DiExtraBundle\Annotation as DI;
use MagicWordBundle\Entity\GameType\Massive;
use MagicWordBundle\Entity\Round;
use MagicWordBundle\Form\Type\MassiveType;
use Symfony\Component\HttpFoundation\Request;
use Ramsey\Uuid\Uuid;

/**
 * @DI\Service("mw_manager.massive")
 */
class MassiveManager
{
    protected $em;
    protected $gridManager;
    protected $roundManager;
    protected $formFactory;
    protected $tokenStorage;
    protected $router;

    /**
     * @DI\InjectParams({
     *      "entityManager" = @DI\Inject("doctrine.orm.entity_manager"),
     *      "gridManager"   = @DI\Inject("mw_manager.grid"),
     *      "roundManager"  = @DI\Inject("mw_manager.round"),
     *      "formFactory"   = @DI\Inject("form.factory"),
     *      "tokenStorage"  = @DI\Inject("security.token_storage"),
     *      "router"        = @DI\Inject("router"),
     * })
     */
    public function __construct($entityManager, $gridManager, $roundManager, $formFactory, $tokenStorage, $router)
    {
        $this->em = $entityManager;
        $this->gridManager = $gridManager;
        $this->roundManager = $roundManager;
        $this->formFactory = $formFactory;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    public function getNextRound(Massive $massive)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $round = $this->em->getRepository('MagicWordBundle:Round')->getNotPlayedYet($massive, $user);

        return $round;
    }

    public function getNextURL(Massive $massive)
    {
        $round = $this->getNextRound($massive);

        $url = (!$round)
            ? $this->router->generate('massive_end', ['id' => $massive->getId()])
            : $this->router->generate('round_play', ['id' => $round->getId()]);

        return $url;
    }

    public function publish(Massive $massive)
    {
        $errors = [];

        $errors = array_merge($errors, $this->isValid($massive));

        foreach ($massive->getRounds() as $round) {
            $errors = array_merge($errors, $this->roundManager->isValid($round));
        }

        if (empty($errors)) {
            $uuid = Uuid::uuid4()->toString();
            $massive->setCode($uuid);
            $massive->setPublished(true);
            $massive->setPublishDate(new \DateTime());
            $this->em->persist($massive);
            $this->em->flush();
        }

        return $errors;
    }

    public function getMyMassives($published)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $massives = $this->em->getRepository('MagicWordBundle:GameType\Massive')->findBy(['author' => $user, 'published' => $published]);

        return $massives;
    }

    public function generateMassiveForm()
    {
        $form = $this->formFactory->createBuilder(MassiveType::class)->getForm()->createView();

        return $form;
    }

    public function handleMassiveForm(Request $request, $massive = null)
    {
        if (!$massive) {
            $massive = new Massive();
        }

        $form = $this->formFactory->createBuilder(MassiveType::class, $massive)->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $massive->setAuthor($this->tokenStorage->getToken()->getUser());

            $this->em->persist($massive);
            $this->em->flush();
        }

        return $massive;
    }

    public function addRushRound(Massive $massive)
    {
        $grid = $this->gridManager->generate($massive->getLanguage());
        $this->roundManager->generateRush($massive, $grid);

        return;
    }

    public function addConquerRound(Massive $massive)
    {
        $this->roundManager->generateConquer($massive);

        return;
    }

    public function removeRound(Massive $massive, Round $round)
    {
        $this->em->remove($round);
        $this->em->flush();

        $this->reorderRounds($massive);

        return;
    }

    public function swapRound(Massive $massive, Round $round, $target)
    {
        $formerOrder = $round->getDisplayOrder();
        $newOrder = $formerOrder + $target;

        $target = $this->em->getRepository('MagicWordBundle:Round')->findOneBy(['game' => $massive, 'displayOrder' => $newOrder]);
        $round->setDisplayOrder($newOrder);
        $target->setDisplayOrder($formerOrder);

        $this->em->persist($round);
        $this->em->persist($target);
        $this->em->flush();

        return;
    }

    private function reorderRounds(Massive $massive)
    {
        $rounds = $massive->getRounds();
        $i = 0;
        foreach ($rounds as $round) {
            $round->setDisplayOrder($i);
            $this->em->persist($round);
            ++$i;
        }
        $this->em->flush();

        return;
    }

    private function isValid(Massive $massive)
    {
        $errors = [];
        if (count($massive->getRounds()) == 0) {
            $errors[] = 'no round...';
        }

        return $errors;
    }
}
