<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\UserDeprecated;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('login', TextType::class, ['label' => 'Логин'])
            ->add('password', PasswordType::class, ['label' => 'Пароль'])
            ->add('conformationPassword', PasswordType::class, ['label' => 'Подтверждения пароля']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserDeprecated::class,
        ]);
    }
}