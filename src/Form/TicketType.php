<?php

namespace App\Form;

use App\Entity\Ticket;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class TicketType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add( 'importance',ChoiceType::class, [
                    'label' => 'Приоритет',
                    'choices'  => [
                    'Низкий' => 1,
                    'Средний' => 2,
                    'Высокий' => 3,
            ],
            ])
                ->add('deadline', DateType::class,
                [
                    'required' => false,
                    'widget' => 'single_text',
                    'label' => 'Срок окончания',
                    'placeholder' => [
                        'year' => 'Год', 'month' => 'Месяц', 'day' => 'День',
                    ],
                ])
                ->add('description', TextareaType::class,
            [
                'required' => true,
                'label' => 'Описание',
                'attr' => [
                    'rows' => "5",
                    'style' => 'font-size:40px',
                ],
                'constraints' => [
                    new Length(['max' => 5000, 'min' => 5])]
            ])
        ->add('captcha', CaptchaType::class, array(
            'width' => 150,
            'height' => 50,
            'length' => 5,
            'label' => 'Введите код с картинки: ',
            'invalid_message' => 'Код введен неверно',
            'background_color' => [255, 255, 255],
            'quality' => 100,
            'distortion' => true,
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}