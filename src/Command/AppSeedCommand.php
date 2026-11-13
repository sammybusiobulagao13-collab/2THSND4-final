<?php

namespace App\Command;

use App\Entity\Message;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed',
    description: 'Seeds the 2THSND4 database with Admin, Staff, Customer, products, and sample data.',
)]
class AppSeedCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding 2THSND4 Database');

        $userRepo = $this->entityManager->getRepository(User::class);
        $productRepo = $this->entityManager->getRepository(Product::class);

        // 1. Seed Admin
        $admin = $userRepo->findOneBy(['email' => 'admin@2thsnd4.com']);
        if (!$admin) {
            $admin = new User();
            $admin->setName('2THSND4 Administrator');
            $admin->setEmail('admin@2thsnd4.com');
            $admin->setUsername('admin');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setIsActive(true);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
            $this->entityManager->persist($admin);
            $io->text('Created Admin: admin@2thsnd4.com / admin123');
        }

        // 2. Seed Staff
        $staff = $userRepo->findOneBy(['email' => 'staff@2thsnd4.com']);
        if (!$staff) {
            $staff = new User();
            $staff->setName('Staff Specialist');
            $staff->setEmail('staff@2thsnd4.com');
            $staff->setUsername('staff');
            $staff->setRoles(['ROLE_STAFF']);
            $staff->setIsActive(true);
            $staff->setPassword($this->passwordHasher->hashPassword($staff, 'staff123'));
            $this->entityManager->persist($staff);
            $io->text('Created Staff: staff@2thsnd4.com / staff123');
        }

        // 3. Seed Customer
        $customer = $userRepo->findOneBy(['email' => 'customer@2thsnd4.com']);
        if (!$customer) {
            $customer = new User();
            $customer->setName('Juan Dela Cruz');
            $customer->setEmail('customer@2thsnd4.com');
            $customer->setUsername('customer');
            $customer->setRoles(['ROLE_USER']);
            $customer->setIsActive(true);
            $customer->setPassword($this->passwordHasher->hashPassword($customer, 'customer123'));
            $this->entityManager->persist($customer);
            $io->text('Created Customer: customer@2thsnd4.com / customer123');
        }

        $this->entityManager->flush();

        // 4. Seed Products
        $productsData = [
            [
                'name' => 'White Shirt',
                'price' => '1299.00',
                'stock' => 15,
                'image' => 'tshirts.jpg.png',
                'category' => 'T-Shirts',
                'description' => 'Heavyweight premium streetwear white tee with high-density 2THSND4 signature branding on chest.',
                'creator' => $admin,
            ],
            [
                'name' => 'Distressed Denim Jeans',
                'price' => '1899.00',
                'stock' => 12,
                'image' => 'jeans.png',
                'category' => 'Jeans & Jorts',
                'description' => 'Classic baggy fit denim with subtle whiskering and reinforced seam stitching.',
                'creator' => $admin,
            ],
            [
                'name' => 'Black Embroidered Cap',
                'price' => '999.00',
                'stock' => 20,
                'image' => 'caps.png',
                'category' => 'Headwear',
                'description' => 'Unstructured 6-panel streetwear cap with tonal 2THSND4 embroidery and brass buckle.',
                'creator' => $staff,
            ],
            [
                'name' => 'Oversized Street Hoody',
                'price' => '1599.00',
                'stock' => 8,
                'image' => 'hoddies.png',
                'category' => 'Hoodies',
                'description' => 'Drop-shoulder fleece hoodie with double-lined hood and kangaroo front pocket.',
                'creator' => $staff,
            ],
            [
                'name' => 'Baggy White Denim Jorts',
                'price' => '1199.00',
                'stock' => 10,
                'image' => 'jorts.png',
                'category' => 'Jeans & Jorts',
                'description' => 'Summer essential below-knee white denim jorts with custom metal hardware.',
                'creator' => $admin,
            ],
            [
                'name' => 'Muscle Cut Graphic Tee',
                'price' => '899.00',
                'stock' => 3, // Low stock test
                'image' => 'muscletee.jpg',
                'category' => 'T-Shirts',
                'description' => 'Raw-edge sleeveless streetwear cut for athletic and casual aesthetic.',
                'creator' => $staff,
            ],
            [
                'name' => 'Vintage Archive Top',
                'price' => '1099.00',
                'stock' => 0, // Out of stock test
                'image' => 'vintage.png',
                'category' => 'Streetwear',
                'description' => 'Limited archived batch piece. Vintage enzyme wash with distressed hem.',
                'creator' => $admin,
            ],
            [
                'name' => 'Street Crop Tee',
                'price' => '799.00',
                'stock' => 14,
                'image' => 'crop.png',
                'category' => 'Streetwear',
                'description' => 'Boxy crop cut top crafted from breathable organic combed cotton.',
                'creator' => $staff,
            ],
        ];

        foreach ($productsData as $data) {
            $existing = $productRepo->findOneBy(['name' => $data['name']]);
            if (!$existing) {
                $p = new Product();
                $p->setName($data['name']);
                $p->setPrice($data['price']);
                $p->setStock($data['stock']);
                $p->setImage($data['image']);
                $p->setCategory($data['category']);
                $p->setDescription($data['description']);
                $p->setCreatedBy($data['creator']);
                $this->entityManager->persist($p);
            }
        }

        // 5. Seed sample contact inquiry
        $msgRepo = $this->entityManager->getRepository(Message::class);
        if ($msgRepo->count([]) === 0) {
            $msg = new Message();
            $msg->setName('Maria Santos');
            $msg->setEmail('maria.santos@gmail.com');
            $msg->setSubject('Restock inquiry for Distressed Jeans');
            $msg->setMessage('Hi 2THSND4 team! Do you plan to restock size XL for the denim jeans? Loving the drops!');
            $this->entityManager->persist($msg);
        }

        $this->entityManager->flush();

        $io->success('Database seeded successfully!');
        $io->table(
            ['Account Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@2thsnd4.com', 'admin123'],
                ['Staff', 'staff@2thsnd4.com', 'staff123'],
                ['Customer', 'customer@2thsnd4.com', 'customer123'],
            ]
        );

        return Command::SUCCESS;
    }
}
