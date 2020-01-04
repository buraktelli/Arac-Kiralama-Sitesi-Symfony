<?php

namespace App\Form;

use App\Entity\Car;
use App\Entity\Category;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Car1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category', EntityType::class,[
                'class' => Category::class,
                'choice_label' => 'title',
            ])
            ->add('title')
            ->add('keywords')
            ->add('description')
            ->add('image'
                , FileType::class, [
                    'label' => 'Car image',

                    // unmapped means that this field is not associated to any entity property
                    'mapped' => false,

                    // make it optional so you don't have to re-upload the PDF file
                    // everytime you edit the Product details
                    'required' => false,

                    // unmapped fields can't define their validation using annotations
                    // in the associated entity, so you can use the PHP constraint classes
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\File([
                            'maxSize' => '4096k',
                            'mimeTypes' => [
                                'image/*',
                            ],
                            'mimeTypesMessage' => 'Please upload a valid PDF document',
                        ])
                    ]
                ])
            ->add('marka')
            ->add('model')
            ->add('phone')
            ->add('price')
            ->add('email')
            ->add('city', ChoiceType::class,[
                'choices' => [
                    'Ankara' => 'Ankara',
                    'Istanbul' => 'Istanbul',
                    'Antalya' => 'Antalya',
                    'Moscow' => 'Moscow',
                    'Barcelona' => 'Barcelona'
                ]
            ])
            ->add('country', ChoiceType::class,[
                'choices' => [
                    'Turkiye' => 'Turkiye',
                    'Spain' => 'Spain',
                    'Greece' => 'Greece',
                    'Russia' => 'Russia',
                    'France' => 'France'
                ]
            ])
            ->add('location')
            ->add('detail',CKEditorType::class,array(
                'config'=>array(
                    'uiColor'=>'#ffffff',
                )
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}
