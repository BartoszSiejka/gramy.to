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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Instrument;
use App\Entity\SongData;
use App\Form\Type\InstrumentType;
use App\Entity\DatabaseVersion;

class InstrumentController extends AbstractController
{
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $instrumentRepository = $em->getRepository(Instrument::class)->findBy(array(), array('name' => 'ASC'));
        
        return $this->render('instrument.html.twig', array('instrumentRepository' => $instrumentRepository));
    }
    
    public function add(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $instrument = new Instrument();
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $form = $this->createForm(InstrumentType::class, $instrument);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $em->persist($data);
            
            $databaseVersion->setInstrument(new \DateTime());
            $em->persist($databaseVersion);
            
            $em->flush();
            
            return $this->redirectToRoute('instrument');
        }
        
        return $this->render('instrumentAdd.html.twig', array('form' => $form->createView()));
    }
    
    public function edit(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $instrument = $em->getRepository(Instrument::class)->findOneBy(array('id' => $id));
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        $form = $this->createForm(InstrumentType::class, $instrument);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $em->persist($data);
            
            $databaseVersion->setInstrument(new \DateTime());
            $em->persist($databaseVersion);
            
            $em->flush();
            
            return $this->redirectToRoute('instrument');
        }
        
        return $this->render('instrumentEdit.html.twig', array('form' => $form->createView()));
    }
    
    public function remove(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $instrument = $em->getRepository(Instrument::class)->findOneBy(array('id' => $id));
        $songDataWithInstrument = $em->getRepository(SongData::class)->findBy(array('instrumentId' => $instrument));
        $databaseVersion = $em->getRepository(DatabaseVersion::class)->findOneBy(array('id' => 1));
        
        if (!$databaseVersion) {
            $databaseVersion = new DatabaseVersion();
        }
        
        foreach ($songDataWithInstrument as $song) {
            $em->remove($song);
        }
        
        $em->remove($instrument);
            
        $databaseVersion->setInstrument(new \DateTime());
        $em->persist($databaseVersion);
        
        $em->flush();
        
        return $this->redirectToRoute('instrument');
    }
}