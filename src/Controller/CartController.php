<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $cartService->getCart(),
            'total' => $cartService->getTotal(),
            'itemCount' => $cartService->getItemCount(),
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST', 'GET'])]
    public function add(Product $product, Request $request, CartService $cartService): Response
    {
        $quantity = (int) ($request->request->get('quantity') ?: $request->query->get('qty', 1));
        $size = (string) ($request->request->get('size') ?: $request->query->get('size', 'M'));

        if ($quantity < 1) {
            $quantity = 1;
        }

        $result = $cartService->add($product->getId(), $quantity, $size);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'cartCount' => $cartService->getItemCount(),
                'cartTotal' => $cartService->getTotal(),
            ]);
        }

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        $redirect = $request->query->get('redirect', 'cart');
        if ($redirect === 'shop') {
            return $this->redirectToRoute('app_shop');
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/update', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, CartService $cartService): Response
    {
        $productId = (int) $request->request->get('product_id');
        $size = (string) $request->request->get('size', 'M');
        $quantity = (int) $request->request->get('quantity', 1);

        $result = $cartService->update($productId, $size, $quantity);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove', methods: ['POST', 'GET'])]
    public function remove(Product $product, Request $request, CartService $cartService): Response
    {
        $size = (string) $request->query->get('size', 'M');
        $cartService->remove($product->getId(), $size);

        $this->addFlash('info', 'Item removed from your cart.');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request, CartService $cartService): Response
    {
        if ($this->isCsrfTokenValid('clear_cart', $request->request->get('_token'))) {
            $cartService->clear();
            $this->addFlash('info', 'Your shopping cart has been cleared.');
        }

        return $this->redirectToRoute('app_cart');
    }
}
