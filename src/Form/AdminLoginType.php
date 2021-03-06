<?php

namespace DovStone\Bundle\BlogAdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use DovStone\Bundle\BlogAdminBundle\Entity\Admin;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AdminLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_username', TextType::class, ['attr' => ['class' => 'form-control', 'placeholder' => "Nom d'utilisateur"]])
            ->add('_password', PasswordType::class, ['attr' => ['class' => 'form-control', 'placeholder' => "Mot de passe"]])
            ->add('submit', SubmitType::class, ['label' => 'CONNEXION', 'attr' => ['class' => 'form-control btn btn-success input-lg']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
        ]);
    }
}
