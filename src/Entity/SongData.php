<?php

/*
 * This file is part of the gramy.to (weplay.it) app.
 *
 * (c) Bartosz Siejka
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="song_data")
 * 
 * @author Bartosz Siejka <siejka.bartosz@gmail.com>
 */
class SongData
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id", nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Instrument")
     * @ORM\JoinColumn(name="instrument_id", referencedColumnName="id", nullable=false)
     * 
     * @var int
     **/
    protected $instrumentId;
    
    /**
     * @ORM\ManyToOne(targetEntity="Song")
     * @ORM\JoinColumn(name="song_id", referencedColumnName="id", nullable=false)
     * 
     * @var int
     **/
    protected $songId;
    
    /**
     * @ORM\Column(type="text", name="content", nullable=true)
     * 
     * @var string
     */
    protected $content;
    
    /**
     * @ORM\Column(type="integer", name="rate", nullable=true)
     * 
     * @var int
     */
    protected $rate;
    
    /**
     * @ORM\Column(type="integer", name="meter", nullable=true)
     * 
     * @var int
     */
    protected $meter;
    
    /**
     * @ORM\Column(type="boolean", name="default_view", nullable=true)
     * 
     * @var bool
     */
    protected $defaultView;
    
    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_at", nullable=false)
     * 
     * @var DateTime
     */
    protected $createdAt;
    
    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", name="updated_at", nullable=true)
     * 
     * @var DateTime
     */
    protected $updatedAt;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set instrumentId
     *
     * @param App\Entity\Instrument $instrumentId
     *
     * @return SongData
     */
    public function setInstrumentId($instrumentId)
    {
        $this->instrumentId = $instrumentId;

        return $this;
    }

    /**
     * Get instrumentId
     *
     * @return App\Entity\Instrument
     */
    public function getInstrumentId()
    {
        return $this->instrumentId;
    }

    /**
     * Set songId
     *
     * @param App\Entity\Song $songId
     *
     * @return SongData
     */
    public function setSongId($songId)
    {
        $this->songId = $songId;

        return $this;
    }

    /**
     * Get songId
     *
     * @return App\Entity\Song
     */
    public function getSongId()
    {
        return $this->songId;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return SongData
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set rate
     *
     * @param int $rate
     *
     * @return SongData
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return int
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set meter
     *
     * @param int $meter
     *
     * @return SongData
     */
    public function setMeter($meter)
    {
        $this->meter = $meter;

        return $this;
    }

    /**
     * Get meter
     *
     * @return int
     */
    public function getMeter()
    {
        return $this->meter;
    }

    /**
     * Set defaultView
     *
     * @param bool $defaultView
     *
     * @return SongData
     */
    public function setDefaultView($defaultView)
    {
        $this->defaultView = $defaultView;

        return $this;
    }

    /**
     * Get defaultView
     *
     * @return bool
     */
    public function getDefaultView()
    {
        return $this->defaultView;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
