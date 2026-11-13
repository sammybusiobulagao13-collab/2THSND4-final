<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_KEY = 'cart';

    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    public function getCart(): array
    {
        $session = $this->getSession();
        $rawCart = $session->get(self::CART_KEY, []);
        $cart = [];

        foreach ($rawCart as $key => $item) {
            $product = $this->productRepository->find($item['id']);
            if (!$product || $product->getStock() <= 0) {
                // If product was deleted or out of stock, remove it
                unset($rawCart[$key]);
                continue;
            }

            $currentStock = $product->getStock();
            $qty = min($item['quantity'], $currentStock);
            $price = (float) $product->getPrice();

            $cart[] = [
                'id' => $product->getId(),
                'product' => $product,
                'name' => $product->getName(),
                'price' => $price,
                'image' => $product->getImage(),
                'quantity' => $qty,
                'size' => $item['size'] ?? 'M',
                'stock' => $currentStock,
                'subtotal' => $price * $qty,
                'cart_key' => $key,
            ];
        }

        $session->set(self::CART_KEY, $rawCart);
        return $cart;
    }

    public function add(int $productId, int $quantity = 1, string $size = 'M'): array
    {
        $session = $this->getSession();
        $cart = $session->get(self::CART_KEY, []);
        $product = $this->productRepository->find($productId);

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        if ($product->getStock() <= 0) {
            return ['success' => false, 'message' => 'Sorry, this product is currently out of stock.'];
        }

        $key = $productId . '_' . $size;
        $currentQty = isset($cart[$key]) ? $cart[$key]['quantity'] : 0;
        $newQty = $currentQty + $quantity;

        if ($newQty > $product->getStock()) {
            return ['success' => false, 'message' => 'Requested quantity exceeds available stock (' . $product->getStock() . ').'];
        }

        $cart[$key] = [
            'id' => $product->getId(),
            'quantity' => $newQty,
            'size' => $size,
        ];

        $session->set(self::CART_KEY, $cart);
        return ['success' => true, 'message' => sprintf('Added "%s" (%s) to cart.', $product->getName(), $size)];
    }

    public function update(int $productId, string $size, int $quantity): array
    {
        $session = $this->getSession();
        $cart = $session->get(self::CART_KEY, []);
        $product = $this->productRepository->find($productId);
        $key = $productId . '_' . $size;

        if (!$product || !isset($cart[$key])) {
            return ['success' => false, 'message' => 'Item not in cart.'];
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
            $session->set(self::CART_KEY, $cart);
            return ['success' => true, 'message' => 'Item removed from cart.'];
        }

        if ($quantity > $product->getStock()) {
            return ['success' => false, 'message' => 'Quantity exceeds available stock.'];
        }

        $cart[$key]['quantity'] = $quantity;
        $session->set(self::CART_KEY, $cart);
        return ['success' => true, 'message' => 'Cart updated.'];
    }

    public function remove(int $productId, string $size): void
    {
        $session = $this->getSession();
        $cart = $session->get(self::CART_KEY, []);
        $key = $productId . '_' . $size;
        if (isset($cart[$key])) {
            unset($cart[$key]);
            $session->set(self::CART_KEY, $cart);
        }
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::CART_KEY);
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $total += $item['subtotal'];
        }
        return $total;
    }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->getCart() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
}
