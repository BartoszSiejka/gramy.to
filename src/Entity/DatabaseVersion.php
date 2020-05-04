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
 * @ORM\Table(name="database_version")
 * 
 * @author Bartosz Siejka <siejka.bartosz@gmail.com>
 */
class DatabaseVersion
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id", nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="datetime", name="song", nullable=true)
     * 
     * @var DateTime
     */
    protected $song;
    
    /**
     * @ORM\Column(type="datetime", name="setlist", nullable=true)
     * 
     * @var DateTime
     */
    protected $setlist;
    
    /**
     * @ORM\Column(type="datetime", name="instrument", nullable=true)
     * 
     * @var DateTime
     */
    protected $instrument;
    
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
     * Set song
     *
     * @param \DateTime $song
     *
     * @return DatabaseVersion
     */
    public function setSong($song)
    {
        $this->song = $song;

        return $this;
    }

    /**
     * Get song
     *
     * @return \DateTime
     */
    public function getSong()
    {
        return $this->song;
    }

    /**
     * Set setlist
     *
     * @param \DateTime $setlist
     *
     * @return DatabaseVersion
     */
    public function setSetlist($setlist)
    {
        $this->setlist = $setlist;

        return $this;
    }

    /**
     * Get setlist
     *
     * @return \DateTime
     */
    public function getSetlist()
    {
        return $this->setlist;
    }

    /**
     * Set instrument
     *
     * @param \DateTime $instrument
     *
     * @return DatabaseVersion
     */
    public function setInstrument($instrument)
    {
        $this->instrument = $instrument;

        return $this;
    }

    /**
     * Get instrument
     *
     * @return \DateTime
     */
    public function getInstrument()
    {
        return $this->instrument;
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
