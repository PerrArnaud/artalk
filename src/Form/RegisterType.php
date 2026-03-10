<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'placeholder' => 'Enter your username',
                ],
                'required' => true,
                'constraints' => [new Assert\Length(min: 3, max: 20)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Enter your email address',
                ],
                'required' => true,
                'constraints' => [new Assert\Email()]
                ],
            )
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'placeholder' => 'Enter your password',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min' => 14,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirm Password',
                'attr' => [
                    'placeholder' => 'Confirm your password',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\EqualTo([
                        'propertyPath' => 'password',
                        'message' => 'Passwords do not match',
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
