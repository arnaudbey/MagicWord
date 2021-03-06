<?php

namespace MagicWordBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Round.
 *
 * @ORM\Table(name="round")
 * @ORM\Entity(repositoryClass="MagicWordBundle\Repository\RoundRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"round"="Round", "rush"="MagicWordBundle\Entity\RoundType\Rush", "conquer" = "MagicWordBundle\Entity\RoundType\Conquer"})
 */
class Round implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="fixedGrid", type="boolean")
     */
    private $fixedGrid = 1;

    /**
     * @ORM\ManyToOne(targetEntity="Game", inversedBy="rounds", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $game;

    /**
     * @ORM\ManyToOne(targetEntity="Innova\LexiconBundle\Entity\Language")
     */
    private $language;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $displayOrder;

    /**
     * @ORM\ManyToOne(targetEntity="Grid")
     */
    private $grid;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $json;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Round
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Round
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set fixedGrid.
     *
     * @param bool $fixedGrid
     *
     * @return Round
     */
    public function setFixedGrid($fixedGrid)
    {
        $this->fixedGrid = $fixedGrid;

        return $this;
    }

    /**
     * Get fixedGrid.
     *
     * @return bool
     */
    public function getFixedGrid()
    {
        return $this->fixedGrid;
    }

    /**
     * Set game.
     *
     * @param \MagicWordBundle\Entity\Game $game
     *
     * @return Round
     */
    public function setGame(\MagicWordBundle\Entity\Game $game = null)
    {
        $this->game = $game;

        return $this;
    }

    /**
     * Get game.
     *
     * @return \MagicWordBundle\Entity\Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * Set grid.
     *
     * @param \MagicWordBundle\Entity\Grid $grid
     *
     * @return Round
     */
    public function setGrid(\MagicWordBundle\Entity\Grid $grid = null)
    {
        $this->grid = $grid;

        return $this;
    }

    /**
     * Get grid.
     *
     * @return \MagicWordBundle\Entity\Grid
     */
    public function getGrid()
    {
        return $this->grid;
    }

    /**
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return Round
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set language.
     *
     * @param \Innova\LexiconBundle\Entity\Language $language
     *
     * @return Round
     */
    public function setLanguage(\Innova\LexiconBundle\Entity\Language $language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language.
     *
     * @return \Innova\LexiconBundle\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    public function jsonSerialize()
    {
        $jsonArray = array(
            'id' => $this->id,
            'findWords' => array(),
            'combos' => array(),
            'constraints' => array(),
            'type' => $this->discr,
        );

        if ($this->discr == 'conquer') {
            foreach ($this->getFindWords() as $findWord) {
                $jsonArray['findWords'][$findWord->getInflection()] =
                [
                    'id' => $findWord->getId(),
                    'hint' => $findWord->getHint(),
                    'lemmaIds' => [],
                ];

                foreach ($findWord->getLemmas() as $lemma) {
                    $jsonArray['findWords'][$findWord->getInflection()]['lemmaIds'][] = $lemma->getId();
                }
            }

            foreach ($this->getCombos() as $combo) {
                $jsonArray['combos'][] =
                    [
                        'id' => $combo->getId(),
                        'length' => $combo->getLenght(),
                        'number' => $combo->getNumber(),
                    ];
            }

            foreach ($this->getConstraints() as $constraint) {
                $jsonArray['constraints'][] =
                    [
                        'id' => $constraint->getId(),
                        'numberToFind' => $constraint->getNumberToFind(),
                        'category' => $constraint->getCategory() ? $constraint->getCategory()->getId() : null,
                        'gender' => $constraint->getGender() ? $constraint->getGender()->getId() : null,
                        'number' => $constraint->getNumber() ? $constraint->getNumber()->getId() : null,
                        'tense' => $constraint->getTense() ? $constraint->getTense()->getId() : null,
                        'mood' => $constraint->getMood() ? $constraint->getMood()->getId() : null,
                        'person' => $constraint->getPerson() ? $constraint->getPerson()->getId() : null,
                    ];
            }
        }

        return $jsonArray;
    }

    /**
     * Set json
     *
     * @param string $json
     *
     * @return Round
     */
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }

    /**
     * Get json
     *
     * @return string
     */
    public function getJson()
    {
        return $this->json;
    }
}
