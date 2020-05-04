<?php

/*
 * This file is part of the gramy.to (weplay.it) app.
 *
 * (c) Bartosz Siejka
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use App\Entity\Instrument;

/**
 * @author Bartosz Siejka <siejka.bartosz@gmail.com>
 */
class SongDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {
        $builder->add('instrumentId', EntityType::class, array(
                    'label' => 'form.song.data.instrument',
                    'class' => Instrument::class, 
                    'choice_label' => 'name', 
                    'required' => true, 
                    'attr' => array('class' => 'form-control', 'onchange' => 'displayMetronome();')))
                ->add('content', CKEditorType::class, array('label' => 'form.song.data.content', 'required' => false, 'attr' => array('class' => 'form-control', 'onchange' => 'refreshToken();')))
                ->add('rate', IntegerType::class, array('label' => 'form.song.data.rate', 'required' => false, 'attr' => array('class' => 'form-control')))
                ->add('meter', IntegerType::class, array('label' => 'form.song.data.meter', 'required' => false, 'attr' => array('class' => 'form-control')))
                ->add('defaultView', CheckboxType::class, array('label' => 'form.song.data.default', 'required' => false, 'attr' => array('class' => 'form-check-input'), 'label_attr' => array('class' => 'form-check-label')))
                ->add('next', SubmitType::class, array('label' => 'form.song.data.next', 'attr' => array('class' => 'btn btn-secondary float-left')))
                ->add('save', SubmitType::class, array('label' => 'form.song.data.save', 'attr' => array('class' => 'btn btn-secondary float-right')));
    }
    
    public function getName() 
    {
        return 'song_data';
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }
}
