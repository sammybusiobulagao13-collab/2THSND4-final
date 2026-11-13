<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingAddress', TextType::class, [
                'label' => 'Complete Street Address',
                'attr' => ['placeholder' => 'House #, Street, Barangay', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Shipping address is required.'])],
            ])
            ->add('city', TextType::class, [
                'label' => 'City / Municipality',
                'attr' => ['placeholder' => 'e.g. Cebu City, Manila, Davao', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'City is required.'])],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Postal / ZIP Code',
                'attr' => ['placeholder' => '6000', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Zip code is required.'])],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Contact Phone Number',
                'attr' => ['placeholder' => '09123456789', 'class' => 'form-input'],
                'constraints' => [new NotBlank(['message' => 'Phone number is required.'])],
            ])
            ->add('deliveryOption', ChoiceType::class, [
                'label' => 'Courier / Delivery Partner',
                'choices' => [
                    'LBC Express (Standard)' => 'LBC',
                    'J&T Express' => 'J&T',
                    'Flash Express' => 'Flash',
                    'Pick-up in Store' => 'Pickup',
                ],
                'attr' => ['class' => 'form-input'],
            ]);
    }
}
