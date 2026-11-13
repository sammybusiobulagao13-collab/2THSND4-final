<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current Password',
                'attr' => ['placeholder' => 'Enter current password', 'class' => 'form-input'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your current password.']),
                    new UserPassword(['message' => 'The current password you entered is incorrect.']),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'New password confirmation does not match.',
                'first_options'  => [
                    'label' => 'New Password',
                    'attr' => ['placeholder' => 'Enter new password (min. 6 characters)', 'class' => 'form-input'],
                    'constraints' => [
                        new NotBlank(['message' => 'Please enter a new password.']),
                        new Length(['min' => 6, 'minMessage' => 'New password must be at least 6 characters.']),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm New Password',
                    'attr' => ['placeholder' => 'Re-type new password', 'class' => 'form-input'],
                ],
            ]);
    }
}
