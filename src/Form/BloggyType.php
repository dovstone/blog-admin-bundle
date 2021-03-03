<?php

namespace DovStone\Bundle\BlogAdminBundle\Form;

use DovStone\Bundle\BlogAdminBundle\Entity\Bloggy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class BloggyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('submit', SubmitType::class, ['label' => "Enregistrer", 'attr' => ['class' => 'bttn-primary no-mg hidden']]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Bloggy::class,
        ]);
    }
}
