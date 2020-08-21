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
use App\Entity\Song;
use App\Entity\SongData;
use App\Entity\Instrument;
use App\Entity\DatabaseVersion;
use App\Entity\SetlistSong;
use App\Form\Type\SongType;
use App\Form\Type\SongDataType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Doctrine\ORM\EntityRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class SongController extends AbstractController
{
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $songRepository = $em->getRepository(Song::class)->findAll();
        
        return $this->render('song.html.twig', array('songsNumber' => count($songRepository)));
    }
    
    public function addSong(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $song = new Song();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $form = $this->createForm(SongType::class, $song);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $em->persist($data);
            
            $databaseVersion->setSong(new \DateTime());
            $em->persist($databaseVersion);
            
            $em->flush();
            
            return $this->redirectToRoute('song_data_add', array('id' => $data->getId()));
        }
        
        return $this->render('songAdd.html.twig', array('form' => $form->createView()));
    }
    
    public function addSongData(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $song = $em->getRepository(Song::class)->findOneBy(array('id' => $id));
        $existingSongData = $em->getRepository(SongData::class)->findBy(array('songId' => $song));
        $instrumentRepository = $em->getRepository(Instrument::class)->findAll();
        $instrumentArray = array();
        $cookies = $request->cookies;
        
        foreach ($instrumentRepository as $one) {
            $instrumentArray[$one->getId()] = $one->getMetronome();
        }
        
        $existingInstrumentForSong = array();
        
        foreach ($existingSongData as $songData) {
            array_push($existingInstrumentForSong, $songData->getInstrumentId());
        }
        
        $songData = new SongData();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $form = $this->createForm(SongDataType::class, $songData);
        $form->add('instrumentId', EntityType::class, array(
                    'label' => 'form.song.data.instrument',
                    'class' => Instrument::class, 
                    'choice_label' => 'name', 
                    'required' => true, 
                    'attr' => array('class' => 'form-control', 'onchange' => 'displayMetronome();'), 
                    'query_builder' => function (EntityRepository $er) use ($existingInstrumentForSong) {
                        $qb = $er->createQueryBuilder('i');
                        
                        if (!empty($existingInstrumentForSong)) {
                            $qb->where('i NOT IN (:existingInstrumentForSong)')
                               ->setParameter('existingInstrumentForSong', $existingInstrumentForSong);
                        }
                        
                        $qb->orderBy('i.name', 'ASC');
                        
                        return $qb;
                    }));
        
        if ($cookies->has('dark_mode') && $cookies->get('dark_mode') == true) {
            $form->add('content', CKEditorType::class, array(
                        'config' => array(
                            'contentsCss' => '/css/invertColorCKE.css',
                        ),
                        'label' => 'form.song.data.content', 'required' => false, 'attr' => array('class' => 'form-control', 'onchange' => 'refreshToken();')));
        } else {
            $form->add('content', CKEditorType::class, array(
                        'label' => 'form.song.data.content', 'required' => false, 'attr' => array('class' => 'form-control', 'onchange' => 'refreshToken();')));
        }
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $data->setSongId($song);
            
            if (empty($existingInstrumentForSong)) {
                $data->setDefaultView(true);
            } elseif ($data->getDefaultView()) {
                $existingDefaultSongData = $em->getRepository(SongData::class)->findOneBy(array('songId' => $song, 'defaultView' => true));
                $existingDefaultSongData->setDefaultView(false);
                $em->persist($existingDefaultSongData);
            }
            
            $em->persist($data);
            
            $databaseVersion->setSong(new \DateTime());
            $em->persist($databaseVersion);
            
            $em->flush();
            
            if ($form->get('next')->isClicked()) {
                return $this->redirectToRoute('song_data_add', array('id' => $song->getId()));
            } elseif ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('song');
            }
        }
        
        return $this->render('songDataAdd.html.twig', array('form' => $form->createView(), 'instrumentArray' => $instrumentArray));
    }
    
    public function editSong(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $song = $em->getRepository(Song::class)->findOneBy(array('id' => $id));
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $form = $this->createForm(SongType::class, $song);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $em->persist($data);
            
            $databaseVersion->setSong(new \DateTime());
            $em->persist($databaseVersion);
            
            $em->flush();
            
            return $this->redirectToRoute('song');
        }
        
        return $this->render('songEdit.html.twig', array('form' => $form->createView()));
    }
    
    public function editSongData(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $songData = $em->getRepository(SongData::class)->findOneBy(array('id' => $id));
        $existingSongData = $em->getRepository(SongData::class)->findBy(array('songId' => $songData->getSongId()));
        $instrumentRepository = $em->getRepository(Instrument::class)->findAll();
        $instrumentArray = array();
        $cookies = $request->cookies;
        
        foreach ($instrumentRepository as $one) {
            $instrumentArray[$one->getId()] = $one->getMetronome();
        }
        
        $existingInstrumentForSong = array();
        
        foreach ($existingSongData as $one) {
            if ($one->getInstrumentId() !== $songData->getInstrumentId()) {
                array_push($existingInstrumentForSong, $one->getInstrumentId());
            }
        }
        
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $form = $this->createForm(SongDataType::class, $songData);
        $form->add('instrumentId', EntityType::class, array(
                    'label' => 'form.song.data.instrument',
                    'class' => Instrument::class, 
                    'choice_label' => 'name', 
                    'required' => true, 
                    'attr' => array('class' => 'form-control', 'onchange' => 'displayMetronome();'), 
                    'query_builder' => function (EntityRepository $er) use ($existingInstrumentForSong) {
                        $qb = $er->createQueryBuilder('i');
                        
                        if (!empty($existingInstrumentForSong)) {
                            $qb->where('i NOT IN (:existingInstrumentForSong)')
                               ->setParameter('existingInstrumentForSong', $existingInstrumentForSong);
                        }
                        
                        $qb->orderBy('i.name', 'ASC');
                        
                        return $qb;
                    }));
        
        if ($cookies->has('dark_mode') && $cookies->get('dark_mode') == true) {
            $form->add('content', CKEditorType::class, array(
                        'config' => array(
                            'contentsCss' => '/css/invertColorCKE.css',
                        ),
                        'label' => 'form.song.data.content', 'required' => false, 'attr' => array('class' => 'form-control', 'onchange' => 'refreshToken();')));
        } else {
            $form->add('content', CKEditorType::class, array(
                        'label' => 'form.song.data.content', 'required' => false, 'attr' => array('class' => 'form-control', 'onchange' => 'refreshToken();')));
        }
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $song = $songData->getSongId();
            
            if (empty($existingInstrumentForSong)) {
                $data->setDefaultView(true);
            } elseif ($data->getDefaultView()) {
                $existingDefaultSongData = $em->getRepository(SongData::class)->findOneBy(array('songId' => $song, 'defaultView' => true));
                $existingDefaultSongData->setDefaultView(false);
                $em->persist($existingDefaultSongData);
            }
            
            $em->persist($data);
            
            $databaseVersion->setSong(new \DateTime());
            $em->persist($databaseVersion);
            
            $em->flush();
            
            return $this->redirectToRoute('song');
        }
        
        return $this->render('songDataEdit.html.twig', array('form' => $form->createView(), 'instrumentArray' => $instrumentArray, 'songData' => $songData));
    }
    
    public function removeSong(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $song = $em->getRepository(Song::class)->findOneBy(array('id' => $id));
        $songData = $em->getRepository(SongData::class)->findBy(array('songId' => $song));
        $setlistSongs = $em->getRepository(SetlistSong::class)->findBy(array('songId' => $song));
        $allSetlistSongs = $em->getRepository(SetlistSong::class)->findAll();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        foreach ($songData as $one) {
            $em->remove($one);
        }
        
        foreach ($setlistSongs as $one) {
            foreach ($allSetlistSongs as $rest) {
                if ($rest->getSetlistId() == $one->getSetlistId() && $rest->getPosition() > $one->getPosition()) {
                    $rest->setPosition($rest->getPosition() - 1);
                    $em->persist($rest);
                }
            }
            
            $em->remove($one);
        }
        
        $em->remove($song);
        
        $databaseVersion->setSong(new \DateTime());
        $em->persist($databaseVersion);
        $em->flush();
        
        return $this->redirectToRoute('song');
    }
    
    public function removeSongData(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $songData = $em->getRepository(SongData::class)->findOneBy(array('id' => $id));
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $datetime = new \DateTime();
        
        if ($songData->getDefaultView()) {
            $existingSongData = $em->getRepository(SongData::class)->createQueryBuilder('esd')->select('esd')
                    ->where('esd.songId = :songId')
                    ->andWhere('esd.id != :id')
                    ->setParameter('songId', $songData->getSongId())
                    ->setParameter('id', $songData->getId())
                    ->getQuery()->setMaxResults(1)->getResult();
            $existingSongData[0]->setDefaultView(true);
            $em->persist($existingSongData[0]);
        }
        
        $em->remove($songData);
        $databaseVersion->setSong($datetime);
        $em->persist($databaseVersion);
        $em->flush();
        
        return $this->redirectToRoute('song');
    }
    
    public function searchSong(TranslatorInterface $translator)
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
        
        $instrumentRepositoryNumber = count($em->getRepository(Instrument::class)->findAll());
        $songDataRepository = $em->getRepository(SongData::class)->findBy(array(), array('songId' => 'ASC'));
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
                $instrumentCounter = 0;
                $songNumber = $songNumber + 1;
                $output .= '<tr>'.
                            '<th scope="row">' . $songNumber . '</th>'.
                            '<td>' . $song->getArtist() . '</td>'.
                            '<td>' . $song->getTitle() . '</td>'.
                            '<td><a href="' . $this->generateUrl('song_edit', array('id' => $song->getId())) . '"><button class="btn btn-info">' . $translator->trans('button.edit') . '</button></a></td>'.
                            '<td><button class="btn btn-danger" data-toggle="modal" data-target="#confirmationModal" data-song-id="' . $song->getId() . '" data-title="' . $song->getTitle() . '" data-artist="' . $song->getArtist() . '">' . $translator->trans('button.remove') . '</button></td>'.
                            '<td>';
                                foreach ($songDataRepository as $data) {
                                    if ($data->getSongId() == $song) {
                                        $instrumentCounter = $instrumentCounter + 1;
                                        $output .='<a href="' . $this->generateUrl('song_data_edit', array('id' => $data->getId())) . '">'.
                                            '<button class="btn btn-info">' . $data->getInstrumentId()->getName() . ($data->getDefaultView() ? ' - domyślny' : '') . '</button>'.
                                        '</a>';
                                    }
                                }
                                
                                if ($instrumentCounter < $instrumentRepositoryNumber) {
                                    $output .= '<a href="' . $this->generateUrl('song_data_add', array('id' => $song->getId())) . '">'.
                                        '<button class="btn btn-secondary w-100 h-100">' . $translator->trans('song.data.add.button') . '</button>'.
                                    '</a>';
                                }
                            $output .= '</td>'.
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
}