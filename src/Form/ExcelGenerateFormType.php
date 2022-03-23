<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class ExcelGenerateFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('created_from', DateType::class,
                [
                    'required' => false,
                    'widget' => 'single_text',
                    'label' => 'От',
                    'placeholder' => [
                        'year' => 'Год', 'month' => 'Месяц', 'day' => 'День',
                    ],
                    'attr' => ['min' => (new \DateTime())->format('Y-m-d')],
                ])
        >add('created_to', DateType::class,
            [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'До',
                'placeholder' => [
                    'year' => 'Год', 'month' => 'Месяц', 'day' => 'День',
                ],
                'attr' => ['min' => (new \DateTime())->format('Y-m-d')],
            ]);

    }
}