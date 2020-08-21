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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Setlist;
use App\Entity\SetlistSong;
use App\Entity\Song;
use App\Form\Type\SetlistType;
use App\Entity\DatabaseVersion;
use Symfony\Contracts\Translation\TranslatorInterface;

class SetlistController extends AbstractController
{
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $setlistRepository = $em->getRepository(Setlist::class)->findBy(array(), array('name' => 'ASC'));
        
        return $this->render('setlist.html.twig', array('setlistRepository' => $setlistRepository));
    }
    
    public function add(Request $request)
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
            
            return $this->redirectToRoute('setlist');
        }
        
        return $this->render('setlistAdd.html.twig', array('form' => $form->createView()));
    }
    
    public function edit(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $setlist = $em->getRepository(Setlist::class)->findOneBy(array('id' => $id));
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
            
            return $this->redirectToRoute('setlist');
        }
        
        return $this->render('setlistEdit.html.twig', array('form' => $form->createView()));
    }
    
    public function remove(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $setlist = $em->getRepository(Setlist::class)->findOneBy(array('id' => $id));
        $setlistSongs = $em->getRepository(SetlistSong::class)->findBy(array('setlistId' => $setlist));
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        foreach ($setlistSongs as $song) {
            $em->remove($song);
        }
        
        $em->remove($setlist);
            
        $databaseVersion->setSetlist(new \DateTime());
        $em->persist($databaseVersion);
        
        $em->flush();
        
        return $this->redirectToRoute('setlist');
    }
    
    public function song($id, $controlSection, $instrumentId)
    {
        $em = $this->getDoctrine()->getManager();
        $setlistRepository = $em->getRepository(Setlist::class)->findOneBy(array('id' => $id));
        $setlistSongRepository = $em->getRepository(SetlistSong::class)->findBy(array('setlistId' => $setlistRepository), array('position' => 'ASC'));
        
        return $this->render('setlistSong.html.twig', array('setlistRepository' => $setlistRepository, 'setlistSongRepository' => $setlistSongRepository, 'controlSection' => $controlSection, 'instrumentId' => $instrumentId));
    }
    
    public function songRemove($id, $controlSection, $instrumentId)
    {
        $em = $this->getDoctrine()->getManager();
        $setlistSongRepository = $em->getRepository(SetlistSong::class)->findOneBy(array('id' => $id));
        $existingSetlistSongsRepository = $em->getRepository(SetlistSong::class)->createQueryBuilder('essr')->select('essr')
                ->where('essr.setlistId = :setlistId')
                ->andWhere('essr.position > :removeSongPosition')
                ->setParameter('setlistId', $setlistSongRepository->getSetlistId())
                ->setParameter('removeSongPosition', $setlistSongRepository->getPosition())
                ->orderBy('essr.position', 'ASC')
                ->getQuery()->getResult();
        
        foreach ($existingSetlistSongsRepository as $song) {
            $song->setPosition($song->getPosition() - 1);
            $em->persist($song);
        }
        
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $em->remove($setlistSongRepository);
            
        $databaseVersion->setSetlist(new \DateTime());
        $em->persist($databaseVersion);
        
        $em->flush();
        
        return $this->redirectToRoute('setlist_song', array('id' => $setlistSongRepository->getSetlistId()->getId(), 'controlSection' => $controlSection, 'instrumentId' => $instrumentId));
    }
    
    public function songAdd($id, $controlSection, $instrumentId) 
    {
        $em = $this->getDoctrine()->getManager();
        $songRepository = $em->getRepository(Song::class)->findAll();
        
        return $this->render('setlistSongAdd.html.twig', array('songsNumber' => count($songRepository), 'setlistId' => $id, 'controlSection' => $controlSection, 'instrumentId' => $instrumentId));
    }
    
    public function songAddSearch(TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $limit = '20';
        $setlistId = $_POST['setlistId']; 
        $instrumentId = $_POST['instrumentId']; 
        $controlSection = $_POST['controlSection']; 
        $page = 1;
        if ($_POST['page'] > 1) {
            $start = (($_POST['page'] - 1) * $limit);
            $page = $_POST['page'];
        } else {
            $start = 0;
        }
        
        $existingPlaylistSongsRepository = $em->getRepository(SetlistSong::class)->findBy(array('setlistId' => $setlistId));
        $existingPlaylistSongs = array();
        
        foreach ($existingPlaylistSongsRepository as $existingPlaylistSong) {
            array_push($existingPlaylistSongs, $existingPlaylistSong->getSongId());
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
                    if (in_array($song, $existingPlaylistSongs)) {
                        $output .= '<td><b>' . $song->getArtist() . '</b></td>'.
                            '<td><b>' . $song->getTitle() . '</b></td>';
                    } else {
                        $output .= '<td>' . $song->getArtist() . '</td>'.
                            '<td>' . $song->getTitle() . '</td>';
                    }
                           $output .= '<td><a href="' . $this->generateUrl('setlist_song_add_proccess', array('setlistId' => $setlistId, 'songId' => $song->getId(), 'controlSection' => $controlSection, 'instrumentId' => $instrumentId)) . '"><button class="btn btn-secondary">' . $translator->trans('button.add') . '</button></a></td>'.
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
    
    public function songAddProccess($setlistId, $songId, $controlSection, $instrumentId) 
    {
        $em = $this->getDoctrine()->getManager();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $setlist = $em->getRepository(Setlist::class)->findOneBy(array('id' => $setlistId));
        $song = $em->getRepository(Song::class)->findOneBy(array('id'=> $songId));
        $existingSetlistSongs = $em->getRepository(SetlistSong::class)->findBy(array('setlistId' => $setlist), array('position' => 'DESC'));
        
        if ($existingSetlistSongs) {
            $position = $existingSetlistSongs[0]->getPosition() + 1;
        } else {
            $position = 1;
        }
        
        $newSetlistSong = new SetlistSong();
        $newSetlistSong->setSetlistId($setlist);
        $newSetlistSong->setSongId($song);
        $newSetlistSong->setPosition($position);
        $em->persist($newSetlistSong);
        $databaseVersion->setSetlist(new \DateTime());
        $em->persist($databaseVersion);
        $em->flush();
        
        return $this->redirectToRoute('setlist_song_add', array('id' => $setlistId, 'controlSection' => $controlSection, 'instrumentId' => $instrumentId));
    }
    
    public function songChangePosition()
    {
        $em = $this->getDoctrine()->getManager();
        $setlistId = $_POST['setlistId'];
        $postSongPositions = $_POST['postSongPositions'];
        $setlistRepository = $em->getRepository(Setlist::class)->findOneBy(array('id' => $setlistId));
        $setlistSongRepository = $em->getRepository(SetlistSong::class)->findBy(array('setlistId' => $setlistRepository));
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        foreach ($setlistSongRepository as $setlistSong) {
            foreach ($postSongPositions as $key => $value) {
                if ($setlistSong->getId() == $value && ($key + 1) !== $setlistSong->getPosition()) { 
                    $setlistSong->setPosition($key + 1);
                    $em->persist($setlistSong);
                }
            }
        }
        
        $databaseVersion->setSetlist(new \DateTime());
        $em->flush();
        
        return new JsonResponse('Position is changed');
    }
}