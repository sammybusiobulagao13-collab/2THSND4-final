<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['placeholder' => 'e.g. Oversized Vintage Tee', 'class' => 'form-input'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a product name.']),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price (PHP ₱)',
                'scale' => 2,
                'attr' => ['placeholder' => '1299.00', 'class' => 'form-input', 'step' => '0.01'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a price.']),
                    new Positive(['message' => 'Price must be greater than 0.']),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Inventory Stock',
                'attr' => ['placeholder' => '10', 'class' => 'form-input'],
                'constraints' => [
                    new NotBlank(['message' => 'Please specify stock quantity.']),
                    new PositiveOrZero(['message' => 'Stock cannot be negative.']),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Streetwear' => 'Streetwear',
                    'T-Shirts' => 'T-Shirts',
                    'Hoodies & Jackets' => 'Hoodies',
                    'Pants & Jorts' => 'Jeans & Jorts',
                    'Headwear & Caps' => 'Headwear',
                    'Accessories' => 'Accessories',
                ],
                'attr' => ['class' => 'form-input'],
            ])
            ->add('image', ChoiceType::class, [
                'label' => 'Product Image',
                'choices' => [
                    'White T-Shirt' => 'tshirts.jpg.png',
                    'Streetwear Jeans' => 'jeans.png',
                    'Black Cap' => 'caps.png',
                    'Oversized Hoody' => 'hoddies.png',
                    'Baggy White Jorts' => 'jorts.png',
                    'Muscle Tee' => 'muscletee.jpg',
                    'Vintage Top' => 'vintage.png',
                    'Crop Top' => 'crop.png',
                    'Fashion Item' => 'fashion.png',
                    'Brand Logo Image' => 'forwbe.png',
                ],
                'attr' => ['class' => 'form-input'],
            ])
            ->add('sizes', TextType::class, [
                'label' => 'Available Sizes (comma-separated)',
                'attr' => ['placeholder' => 'S,M,L,XL,XXL', 'class' => 'form-input'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Product Description',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Describe fabric, cut, style, and fit...', 'class' => 'form-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
