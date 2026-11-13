<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr' => ['placeholder' => 'Juan Dela Cruz', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Please provide full name.'])],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'user@example.com', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Please provide email.'])],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => ['placeholder' => 'juandc', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Please provide username.'])],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'System Role',
                'mapped' => false,
                'choices' => [
                    'Administrator' => 'ROLE_ADMIN',
                    'Staff Member' => 'ROLE_STAFF',
                    'Customer' => 'ROLE_USER',
                ],
                'data' => $options['current_role'],
                'attr' => ['class' => 'form-input'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'Reset Password (leave empty to keep current)' : 'Password',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => ['placeholder' => $isEdit ? 'Enter new password if changing' : 'Enter password', 'class' => 'form-input'],
                'constraints' => $isEdit ? [] : [
                    new NotBlank(['message' => 'Password is required for new accounts.']),
                    new Length(['min' => 6, 'minMessage' => 'Password must be at least 6 characters.']),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Account Active (uncheck to disable or archive user)',
                'required' => false,
                'attr' => ['class' => 'form-checkbox'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'current_role' => 'ROLE_USER',
        ]);
    }
}
