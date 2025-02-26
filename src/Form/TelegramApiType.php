<?php


namespace App\Form;


use App\Entity\TelegramApi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TelegramApiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('botToken', TextType::class, ['label' => 'Токен бота Telegram'])
            ->add('chat_id', TextType::class, ['label' => 'Id чата']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TelegramApi::class,
        ]);
    }
}