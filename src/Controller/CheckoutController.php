<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\CheckoutType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(
        Request $request,
        CartService $cartService,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $cart = $cartService->getCart();
        $subtotal = $cartService->getSubtotal();
        $shippingFee = $cartService->getShippingFee();
        $total = $cartService->getTotal();

        if (empty($cart)) {
            $this->addFlash('warning', 'Your shopping cart is empty! Please add products before checking out.');
            return $this->redirectToRoute('app_shop');
        }

        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            // Verify stock before completing order
            foreach ($cart as $item) {
                $product = $productRepository->find($item['id']);
                if (!$product || $product->getStock() < $item['quantity']) {
                    $this->addFlash('danger', sprintf(
                        'Insufficient stock for "%s". Only %d remaining.',
                        $item['name'],
                        $product ? $product->getStock() : 0
                    ));
                    return $this->redirectToRoute('app_cart');
                }
            }

            // Create Order
            $order = new Order();
            $order->setUser($this->getUser());
            $order->setTotal($total);
            $order->setStatus('Processing');
            $order->setPaymentMethod('Cash on Delivery');
            $order->setDeliveryOption($formData['deliveryOption']);
            $order->setShippingAddress($formData['shippingAddress']);
            $order->setCity($formData['city']);
            $order->setZipCode($formData['zipCode']);
            $order->setPhone($formData['phone']);

            // Create OrderItems and decrement stock
            foreach ($cart as $item) {
                $product = $productRepository->find($item['id']);
                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setProductName($item['name']);
                $orderItem->setPrice($item['price']);
                $orderItem->setQuantity($item['quantity']);
                $orderItem->setSize($item['size']);

                $order->addOrderItem($orderItem);

                // Deduct stock
                $product->setStock($product->getStock() - $item['quantity']);
                $entityManager->persist($product);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            // Clear the cart
            $cartService->clear();

            $this->addFlash('success', 'Order #' . $order->getOrderNumber() . ' placed successfully! Thank you for supporting 2THSND4.');
            return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
        }

        return $this->render('checkout/index.html.twig', [
            'checkoutForm' => $form->createView(),
            'cart' => $cart,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'total' => $total,
            'isFreeShipping' => $cartService->isFreeShipping(),
            'remainingForFreeShipping' => $cartService->getRemainingForFreeShipping(),
        ]);
    }

    #[Route('/order/confirmation/{id}', name: 'app_order_confirmation', requirements: ['id' => '\d+'])]
    public function confirmation(Order $order): Response
    {
        // Only owner or admin can view confirmation
        if ($order->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You are not authorized to view this order confirmation.');
        }

        return $this->render('checkout/confirmation.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/my-orders', name: 'app_my_orders')]
    public function myOrders(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findByUser($this->getUser());

        return $this->render('checkout/my_orders.html.twig', [
            'orders' => $orders,
        ]);
    }
}
