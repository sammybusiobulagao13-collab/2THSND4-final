<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr' => ['class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Name cannot be blank.'])],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => ['class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Username cannot be blank.'])],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'disabled' => true,
                'attr' => ['class' => 'form-input', 'style' => 'opacity: 0.7; cursor: not-allowed;'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
