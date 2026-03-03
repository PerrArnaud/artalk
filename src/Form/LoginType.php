<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Identifiant', TextType::class, [
                'label' => 'Username or Email',
                'attr' => [
                    'placeholder' => 'Enter your username or email address',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a username or email address',
                    ]),
                ],
            ])
            ->add('Password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'placeholder' => 'Enter your password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
