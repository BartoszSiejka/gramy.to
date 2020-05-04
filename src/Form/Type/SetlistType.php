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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * @author Bartosz Siejka <siejka.bartosz@gmail.com>
 */
class SetlistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {
        $builder->add('name', TextType::class, array('label' => 'form.setlist.name', 'required' => true, 'attr' => array('class' => 'form-control')))
                ->add('save', SubmitType::class, array('label' => 'form.save', 'attr' => array('class' => 'btn btn-secondary float-right')));
    }
    
    public function getName() 
    {
        return 'setlist';
    }
}
