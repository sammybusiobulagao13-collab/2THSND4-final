<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr' => ['placeholder' => 'Full Name', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Full name is required.'])],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Email Address', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Email is required.'])],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => ['placeholder' => 'Username', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Username is required.'])],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Passwords must match.',
                'first_options'  => [
                    'label' => 'Password',
                    'attr' => ['placeholder' => 'Password (min. 6 chars)', 'class' => 'form-input'],
                    'constraints' => [
                        new NotBlank(['message' => 'Password is required.']),
                        new Length(['min' => 6, 'minMessage' => 'Password must be at least 6 characters long.']),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => ['placeholder' => 'Confirm Password', 'class' => 'form-input'],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
