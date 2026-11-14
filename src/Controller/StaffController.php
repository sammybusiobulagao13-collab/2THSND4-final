<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
#[Route('/staff')]
class StaffController extends AbstractController
{
    #[Route('', name: 'app_staff_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_staff_dashboard');
    }

    #[Route('/dashboard', name: 'app_staff_dashboard')]
    public function dashboard(
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        MessageRepository $messageRepository
    ): Response {
        return $this->render('staff/dashboard.html.twig', [
            'totalProducts'    => $productRepository->count([]),
            'myProducts'       => count($productRepository->findByCreator($this->getUser())),
            'totalOrders'      => $orderRepository->count([]),
            'processingOrders' => $orderRepository->countByStatus('Processing'),
            'approvedOrders'   => $orderRepository->countByStatus('Approved'),
            'shippedOrders'    => $orderRepository->countByStatus('Shipped'),
            'deliveredOrders'  => $orderRepository->countByStatus('Delivered'),
            'cancelledOrders'  => $orderRepository->countByStatus('Cancelled'),
            'recentOrders'     => $orderRepository->findRecent(6),
            'unreadMessages'   => $messageRepository->countUnread(),
            'recentMessages'   => $messageRepository->findAllOrdered(),
        ]);
    }

    #[Route('/orders', name: 'app_staff_orders')]
    public function orders(Request $request, OrderRepository $orderRepository): Response
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $orders = $orderRepository->searchOrders($search, $status);

        return $this->render('staff/orders.html.twig', [
            'orders'           => $orders,
            'search'           => $search,
            'status'           => $status,
            'totalOrders'      => $orderRepository->count([]),
            'processingOrders' => $orderRepository->countByStatus('Processing'),
            'approvedOrders'   => $orderRepository->countByStatus('Approved'),
            'shippedOrders'    => $orderRepository->countByStatus('Shipped'),
            'deliveredOrders'  => $orderRepository->countByStatus('Delivered'),
            'cancelledOrders'  => $orderRepository->countByStatus('Cancelled'),
        ]);
    }

    #[Route('/inquiries', name: 'app_staff_inquiries')]
    public function inquiries(MessageRepository $messageRepository): Response
    {
        return $this->render('staff/inquiries.html.twig', [
            'messages'      => $messageRepository->findAllOrdered(),
            'unreadCount'   => $messageRepository->countUnread(),
            'totalMessages' => $messageRepository->count([]),
        ]);
    }

    #[Route('/products', name: 'app_staff_products')]
    public function products(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('search');
        $filter = $request->query->get('filter'); // 'all' or 'my'

        if ($filter === 'my') {
            $products = $productRepository->findByCreator($this->getUser());
        } else {
            $products = $productRepository->searchAndFilter($search);
        }

        return $this->render('staff/products.html.twig', [
            'products' => $products,
            'search' => $search,
            'currentFilter' => $filter ?: 'all',
        ]);
    }

    #[Route('/product/new', name: 'app_staff_product_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $product->setCreatedBy($this->getUser());

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Product "%s" created successfully!', $product->getName()));
            return $this->redirectToRoute('app_staff_products');
        }

        return $this->render('staff/product_form.html.twig', [
            'productForm' => $form->createView(),
            'isEdit' => false,
        ]);
    }

    #[Route('/product/{id}/edit', name: 'app_staff_product_edit', requirements: ['id' => '\d+'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        // STRICT RUBRIC CHECK: Staff can ONLY edit their own records!
        if ($product->getCreatedBy() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access Denied: As a staff member, you can only edit records that you personally created.');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', sprintf('Product "%s" updated successfully!', $product->getName()));
            return $this->redirectToRoute('app_staff_products');
        }

        return $this->render('staff/product_form.html.twig', [
            'productForm' => $form->createView(),
            'product' => $product,
            'isEdit' => true,
        ]);
    }

    #[Route('/product/{id}/delete', name: 'app_staff_product_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        // STRICT RUBRIC CHECK: Staff can ONLY delete their own records!
        if ($product->getCreatedBy() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access Denied: As a staff member, you can only delete records that you personally created.');
        }

        if ($this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            $productName = $product->getName();
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Product "%s" deleted successfully.', $productName));
        } else {
            $this->addFlash('danger', 'Invalid security token for deletion.');
        }

        return $this->redirectToRoute('app_staff_products');
    }

    // ── Customer Management ──────────────────────────────────────────────────

    #[Route('/customers', name: 'app_staff_customers')]
    public function customers(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('search');
        $customers = $userRepository->findCustomers($search);

        return $this->render('staff/customers.html.twig', [
            'customers'         => $customers,
            'search'            => $search,
            'totalCustomers'    => $userRepository->countCustomers(),
            'activeCustomers'   => $userRepository->countActiveCustomers(),
            'inactiveCustomers' => $userRepository->countInactiveCustomers(),
        ]);
    }

    #[Route('/customers/{id}/toggle', name: 'app_staff_customer_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleCustomer(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        // STRICT GUARD: Staff can only toggle pure customers — never staff/admin accounts
        $roles = $user->getRoles();
        if (in_array('ROLE_STAFF', $roles) || in_array('ROLE_ADMIN', $roles)) {
            $this->addFlash('danger', 'Access Denied: You cannot modify staff or admin accounts.');
            return $this->redirectToRoute('app_staff_customers');
        }

        if (!$this->isCsrfTokenValid('toggle_customer_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token.');
            return $this->redirectToRoute('app_staff_customers');
        }

        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $status = $user->isActive() ? 'activated' : 'deactivated';
        $this->addFlash('success', sprintf('Customer "%s" has been %s.', $user->getName(), $status));

        return $this->redirectToRoute('app_staff_customers');
    }
}
