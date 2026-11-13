<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    #[Route('/shop', name: 'app_shop')]
    public function shop(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('search');
        $category = $request->query->get('category');

        $products = $productRepository->searchAndFilter($search, $category);

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'search' => $search,
            'currentCategory' => $category,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_detail', requirements: ['id' => '\d+'])]
    public function detail(Product $product): Response
    {
        return $this->render('shop/detail.html.twig', [
            'product' => $product,
        ]);
    }
}
