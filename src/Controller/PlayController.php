<?php

/*
 * This file is part of the gramy.to (weplay.it) app.
 *
 * (c) Bartosz Siejka
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Instrument;
use App\Entity\Song;
use App\Entity\SongData;
use App\Entity\Setlist;
use App\Entity\SetlistSong;
use App\Entity\DatabaseVersion;
use App\Form\Type\SetlistType;

class PlayController extends AbstractController
{
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $instrumentRepository = $em->getRepository(Instrument::class)->findBy(array(), array('name' => 'ASC'));
        
        return $this->render('play.html.twig', array('instrumentRepository' => $instrumentRepository));
    }
    
    public function role($instrumentId)
    {
        $em = $this->getDoctrine()->getManager();
        $instrument = $em->getRepository(Instrument::class)->findOneBy(array('id' => $instrumentId));
        $metronome = $instrument->getMetronome();
        
        return $this->render('role.html.twig', array('instrumentId' => $instrumentId, 'metronome' => $metronome));
    }
    
    public function display($instrumentId)
    {        
        return $this->render('display.html.twig', array('instrumentId' => $instrumentId));
    }
    
    public function songRefresh($instrumentId)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        $em = $this->getDoctrine()->getManager();
        $song = $em->getRepository(Song::class)->findOneBy(array('isSet' => true));
        
        if (!$song) {
            $songRepository = $em->getRepository(Song::class)->findAll();
            $song = $songRepository[0];
        }
        
        $instrument = $em->getRepository(Instrument::class)->findOneBy(array('id' => $instrumentId));
        $songData = $em->getRepository(SongData::class)->findOneBy(array('songId' => $song, 'instrumentId' => $instrument));

        if (!$songData) {
            $songData = $em->getRepository(SongData::class)->findOneBy(array('songId' => $song, 'defaultView' => true));
        }

        $data = array(
            'id' => $song->getId(),
            'artist' => $song->getArtist(),
            'title' => $song->getTitle(),
            'metronome' => $instrument->getMetronome(),
            'content' => $songData->getContent(),
            'rate' => $songData->getRate(),
            'meter' => $songData->getMeter()
        );

        $str = json_encode($data);
        echo "data: {$str}\n\n";
        flush();
    }
    
    function control($instrumentId, $setlistId, $songId)
    {
        $em = $this->getDoctrine()->getManager();
        $instrument = $em->getRepository(Instrument::class)->findOneBy(array('id' => $instrumentId));
        $song = null;
        $songData = null;
        $setlist = null;
        $setlistSong = null;
        $setlistSongArray = array();
        $setlistSongPositionArray = array();
        
        if ($songId !== 0) {
            $song = $em->getRepository(Song::class)->findOneBy(array('id' => $songId));
            $songData = $em->getRepository(SongData::class)->findOneBy(array('songId' => $song, 'instrumentId' => $instrument));
            
            if (!$songData) {
                $songData = $em->getRepository(SongData::class)->findOneBy(array('songId' => $song, 'defaultView' => true));
            }
        }
        
        if ($setlistId !== 0) {
            $setlist = $em->getRepository(Setlist::class)->findOneBy(array('id' => $setlistId));
            $setlistSong = $em->getRepository(SetlistSong::class)->findBy(array('setlistId' => $setlist), array('position' => 'ASC'));
            
            if (!$setlistSong) {
                $setlistSong = $em->getRepository(SetlistSong::class)->findOneBy(array('position' => 1));
            }
            
            foreach ($setlistSong as $one) {
                $setlistSongArray[$one->getSongId()->getId()] = $one->getPosition();
                $setlistSongPositionArray[$one->getPosition()] = $one->getSongId()->getId();
            }
        }
        
        return $this->render('control.html.twig', array('instrument' => $instrument, 'setlistId' => $setlistId, 'songId' => $songId, 'song' => $song, 'songData' => $songData, 'setlist' => $setlist, 'setlistSong' => $setlistSong, 'setlistSongArray' => $setlistSongArray, 'setlistSongPositionArray' => $setlistSongPositionArray));
    }
    
    function quickSong($instrumentId)
    {
        $em = $this->getDoctrine()->getManager();
        $songRepository = $em->getRepository(Song::class)->findAll();
        
        return $this->render('quickSong.html.twig', array('songsNumber' => count($songRepository), 'instrumentId' => $instrumentId));
        
    }
    
    public function quickSongAdd(TranslatorInterface $translator, $instrumentId)
    {
        $em = $this->getDoctrine()->getManager();
        $limit = '20';
        $page = 1;
        if ($_POST['page'] > 1) {
            $start = (($_POST['page'] - 1) * $limit);
            $page = $_POST['page'];
        } else {
            $start = 0;
        }
        
        $songRepository = $em->getRepository(Song::class)->createQueryBuilder('s');

        if ($_POST['query'] != '') {
            $index = explode(' ', $_POST['query']);

            foreach ($index as $val) {
                $result[] = $songRepository->expr()->orX()
                                          ->add($songRepository->expr()->like("CONCAT(s.artist, '')", "'%$val%'"))
                                          ->add($songRepository->expr()->like("CONCAT(s.title, '')", "'%$val%'"))
                                        ;
            }

            $songRepository->andWhere(call_user_func_array(array($songRepository->expr(),"orx"),$result));
        }

        $totalData = count($songRepository->getQuery()->getResult());  
        $songRepository = $songRepository->orderBy('s.artist', 'ASC')
                ->addOrderBy('s.title', 'ASC')
                ->setMaxResults($limit)
                ->setFirstResult($start)
                ->getQuery()->getResult();      
        $output = '<tr><td colspan="6" style="text-align: center;"><span style="color: #FF0000; font-weight: bold;">' . $translator->trans('song.searchbox.not.found') . '</span></td></tr>';
        
        if ($songRepository) {
            $output = '';
            $songNumber = $start;
            
            foreach ($songRepository as $song) {
                $songNumber = $songNumber + 1;
                $output .= '<tr>'.
                    '<th scope="row">' . $songNumber . '</th>';
                $output .= '<td>' . $song->getArtist() . '</td>'.
                    '<td>' . $song->getTitle() . '</td>';
                $output .= '<td><a href="' . $this->generateUrl('control', array('instrumentId' => $instrumentId, 'setlistId' => 0, 'songId' => $song->getId())) . '"><button class="btn btn-secondary w-100">' . $translator->trans('control.quick.song.add.play') . '</button></a></td>'.
                        '</tr>';
            }
        
            $output .= '<tr><td colspan="6"><ul class="pagination justify-content-center">';
            $totalLinks = ceil($totalData / $limit);
            $previousLink = '';
            $nextLink = '';
            $pageLink = '';

            if ($totalLinks > 4) {
                if ($page < 5) {
                    for ($count = 1; $count <= 5; $count++) {
                        $pageArray[] = $count;
                    }

                    $pageArray[] = '...';
                    $pageArray[] = $totalLinks;
                } else {
                    $endLimit = $totalLinks - 5;
                    if ($page > $endLimit) {
                        $pageArray[] = 1;
                        $pageArray[] = '...';

                        for ($count = $endLimit; $count <= $totalLinks; $count++) {
                            $pageArray[] = $count;
                        }
                    } else {
                        $pageArray[] = 1;
                        $pageArray[] = '...';

                        for ($count = $page - 1; $count <= $page + 1; $count++) {
                            $pageArray[] = $count;
                        }

                        $pageArray[] = '...';
                        $pageArray[] = $totalLinks;
                    }
                }
            } else {
                for ($count = 1; $count <= $totalLinks; $count++) {
                    $pageArray[] = $count;
                }
            }

            for ($count = 0; $count < count($pageArray); $count++) {
                if ($page == $pageArray[$count]) {
                    $pageLink .= '
                        <li class="page-item active">
                            <a class="page-link" href="#">' . $pageArray[$count] . '</a>
                        </li>
                        ';
                    $previousId = $pageArray[$count] - 1;

                    if ($previousId > 0) {
                        $previousLink = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $previousId . '">' . $translator->trans('pagination.previous') . '</a></li>';
                    } else {
                        $previousLink = '
                        <li class="page-item disabled">
                            <a class="page-link" href="#">' . $translator->trans('pagination.previous') . '</a>
                        </li>
                        ';
                    }

                    $nextId = $pageArray[$count] + 1;

                    if ($nextId > $totalLinks) {
                        $nextLink = '
                        <li class="page-item disabled">
                            <a class="page-link" href="#">' . $translator->trans('pagination.next') . '</a>
                        </li>
                          ';
                    } else {
                        $nextLink = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $nextId . '">' . $translator->trans('pagination.next') . '</a></li>';
                    }
                } else {
                    if ($pageArray[$count] == '...') {
                        $pageLink .= '
                        <li class="page-item disabled">
                            <a class="page-link" href="#">...</a>
                        </li>
                        ';
                    } else {
                        $pageLink .= '
                        <li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $pageArray[$count] . '">' . $pageArray[$count] . '</a></li>
          ';
                    }
                }
            }

            $output .= $previousLink . $pageLink . $nextLink;
            $output .= '</ul></td></tr>';
        }
        
        return new JsonResponse($output);
    }
    
    public function setSong($instrumentId) 
    {
        $em = $this->getDoctrine()->getManager();
        
        try {
            $newId = $_POST['songId'];
            $songRepository = $em->getRepository(Song::class);
            $previuosSong = $songRepository->findOneBy(array('isSet' => true));
            $currentSong = $songRepository->findOneBy(array('id' => $newId));
            
            if ($previuosSong == $currentSong) {
                return new JsonResponse('done');
            }

            if ($previuosSong) {
                $previuosSong->setIsSet(false);
                $em->persist($previuosSong);
            }

            $currentSong->setIsSet(true);
            $em->persist($currentSong);
            $em->flush();
            $output = array('done' => true, 'e' => '');
            
            return new JsonResponse($output);
        } catch (Exception $e) {
            $output = array('done' => false, 'e' => $e);
            
            return new JsonResponse($output);
        }
    }
    
    public function setlist($instrumentId)
    {
        $em = $this->getDoctrine()->getManager();
        $setlistRepository = $em->getRepository(Setlist::class)->findBy(array(), array('name' => 'ASC'));
        $setlistSongRepository = $em->getRepository(SetlistSong::class)->findBy(array('setlistId' => $setlistRepository, 'position' => 1));
        $setlistSongArray = array();
        $tempSong = $setlistSongRepository[0]->getSongId();
        
        foreach ($setlistSongRepository as $setlistSong) {
            $setlistSongArray[$setlistSong->getSetlistId()->getId()] = $setlistSong->getSongId()->getId();
        }
        
        foreach ($setlistRepository as $one) {
            if (!array_key_exists($one->getId(), $setlistSongArray)) {
                $setlistSongArray[$one->getId()] = $tempSong->getId();
            }
        }
        
        return $this->render('controlSetlist.html.twig', array('setlistRepository' => $setlistRepository, 'setlistSongArray' => $setlistSongArray, 'instrumentId' => $instrumentId));
    }
    
    public function setlistAdd(Request $request, $instrumentId)
    {
        $em = $this->getDoctrine()->getManager();
        $setlist = new Setlist();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $form = $this->createForm(SetlistType::class, $setlist);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $em->persist($data);
            
            $databaseVersion->setSetlist(new \DateTime());
            $em->persist($databaseVersion);
            
            $em->flush();
            
            return $this->redirectToRoute('control_setlist', array('instrumentId' => $instrumentId));
        }
        
        return $this->render('setlistAdd.html.twig', array('form' => $form->createView()));
    }
}