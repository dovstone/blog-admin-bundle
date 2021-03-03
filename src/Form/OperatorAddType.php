<?php

namespace DovStone\Bundle\BlogAdminBundle\Form;

use DovStone\Bundle\BlogAdminBundle\Entity\Admin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class OperatorAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('lastname', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('email', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('submit', SubmitType::class, ['label' => "Ajouter", 'attr' => ['class' => 'btn btn-success']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
        ]);
    }
}
