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
 * @ORM\Table(name="setlist_song")
 * 
 * @author Bartosz Siejka <siejka.bartosz@gmail.com>
 */
class SetlistSong
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id", nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Setlist")
     * @ORM\JoinColumn(name="setlist_id", referencedColumnName="id", nullable=false)
     * 
     * @var int
     **/
    protected $setlistId;
    
    /**
     * @ORM\ManyToOne(targetEntity="Song")
     * @ORM\JoinColumn(name="song_id", referencedColumnName="id", nullable=false)
     * 
     * @var int
     **/
    protected $songId;
    
    /**
     * @ORM\Column(type="integer", name="position", nullable=false)
     * 
     * @var int
     */
    protected $position;
    
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
     * Set setlistId
     *
     * @param App\Entity\Setlist $setlistId
     *
     * @return SetlistSong
     */
    public function setSetlistId($setlistId)
    {
        $this->setlistId = $setlistId;

        return $this;
    }

    /**
     * Get setlistId
     *
     * @return App\Entity\Setlist
     */
    public function getSetlistId()
    {
        return $this->setlistId;
    }

    /**
     * Set songId
     *
     * @param App\Entity\Song $songId
     *
     * @return SetlistSong
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
     * Set position
     *
     * @param int $position
     *
     * @return SetlistSong
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
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
