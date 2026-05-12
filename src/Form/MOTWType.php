<?php

namespace App\Form;

use App\Entity\MOTW;
use App\Entity\ArtType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints as Assert;

class MOTWType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'placeholder' => 'Piece of Art Name',
                ],
                'required' => true,
            ])
            ->add('Date', DateType::class, [
                'label' => 'Date de l\'oeuvre',
                'widget' => 'text',
                'format' => 'dd-MM-yyyy',
                'required' => true,
                'years' => range(-1000, date('Y')),
                'placeholder' => [
                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
                ],
                'attr' => [
                    'placeholder' => 'JJ-MM-AAAA',
                ],
            ])
            ->add('Artist', TextType::class, [
                'label' => 'Artist',
                'attr' => [
                    'placeholder' => 'Enter the artist name',
                ],
                'required' => true,
            ])
            ->add('artType', EntityType::class, [
                'class' => ArtType::class,
                'choice_label' => 'name',
                'label' => 'Type d\'art',
                'required' => false,
                'placeholder' => '— Sélectionner un type d\'art —',
            ])
            ->add('visual', FileType::class, [
                'label' => 'Visual (Image file)',
                'mapped' => false,
                'required' => false,
                'constraints' => new Assert\File(
                    maxSize: '5M',
                    mimeTypes: [
                        'image/jpeg',
                        'image/jpg',
                        'image/png',
                        'image/gif',
                    ],
                    mimeTypesMessage: 'Please upload a valid image file (JPEG, JPG, PNG, GIF).'
                ),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MOTW::class,
        ]);
    }
}
