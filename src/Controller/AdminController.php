<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\User;
use App\Form\ProductType;
use App\Form\UserType;
use App\Repository\MessageRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    // ==========================================
    // 1. ADMIN DASHBOARD
    // ==========================================
    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        MessageRepository $messageRepository
    ): Response {
        $totalUsers = $userRepository->count([]);
        $totalStaff = $userRepository->countStaff();
        $totalCustomers = $userRepository->countCustomers();
        $totalAdmins = $userRepository->countAdmins();

        $totalProducts = $productRepository->count([]);
        $totalOrders = $orderRepository->count([]);
        $totalMessages = $messageRepository->count([]);

        // Total system records combined
        $totalRecords = $totalProducts + $totalOrders + $totalMessages;

        $lowStock = $productRepository->countLowStock();
        $outOfStock = $productRepository->countOutOfStock();
        $processingOrders = $orderRepository->countByStatus('Processing');
        $unreadMessages = $messageRepository->countUnread();
        $revenue = $orderRepository->calculateTotalRevenue();

        $recentOrders = $orderRepository->findRecent(6);

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalStaff' => $totalStaff,
            'totalCustomers' => $totalCustomers,
            'totalAdmins' => $totalAdmins,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalMessages' => $totalMessages,
            'totalRecords' => $totalRecords,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'processingOrders' => $processingOrders,
            'unreadMessages' => $unreadMessages,
            'revenue' => $revenue,
            'recentOrders' => $recentOrders,
        ]);
    }

    // ==========================================
    // 2. USER & STAFF MANAGEMENT (CRUD)
    // ==========================================
    #[Route('/users', name: 'app_admin_users')]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('search');
        $roleFilter = $request->query->get('role');

        $users = $userRepository->searchUsers($search, $roleFilter);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'totalUsers' => $userRepository->count([]),
            'totalStaff' => $userRepository->countStaff(),
            'totalAdmins' => $userRepository->countAdmins(),
            'totalCustomers' => $userRepository->countCustomers(),
        ]);
    }

    #[Route('/users/new', name: 'app_admin_user_new')]
    public function newUser(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => false,
            'current_role' => 'ROLE_STAFF',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $role = $form->get('role')->getData();
            $user->setRoles([$role]);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('User "%s" created successfully with role %s!', $user->getName(), $user->getPrimaryRoleLabel()));
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/form.html.twig', [
            'userForm' => $form->createView(),
            'isEdit' => false,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_user_edit', requirements: ['id' => '\d+'])]
    public function editUser(
        User $user,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
            'current_role' => $user->getPrimaryRole(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $role = $form->get('role')->getData();
            $user->setRoles([$role]);

            $entityManager->flush();

            $this->addFlash('success', sprintf('User "%s" updated successfully!', $user->getName()));
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/form.html.twig', [
            'userForm' => $form->createView(),
            'user' => $user,
            'isEdit' => true,
        ]);
    }

    #[Route('/users/{id}/toggle-status', name: 'app_admin_user_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleUserStatus(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'You cannot disable your own administrator account.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('toggle_user_' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $entityManager->flush();

            $statusText = $user->isActive() ? 'activated' : 'disabled / archived';
            $this->addFlash('info', sprintf('User "%s" is now %s.', $user->getName(), $statusText));
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Action forbidden: You cannot delete your own account.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $userName = $user->getName();
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf('User account "%s" deleted successfully.', $userName));
        } else {
            $this->addFlash('danger', 'Invalid CSRF security token.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    // ==========================================
    // 3. PRODUCT MANAGEMENT (FULL DATA ACCESS)
    // ==========================================
    #[Route('/products', name: 'app_admin_products')]
    public function products(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('search');
        $category = $request->query->get('category');
        $stockStatus = $request->query->get('stock');

        $products = $productRepository->searchAndFilter($search, $category, $stockStatus);

        return $this->render('admin/products/index.html.twig', [
            'products' => $products,
            'search' => $search,
            'category' => $category,
            'stockStatus' => $stockStatus,
            'totalProducts' => $productRepository->count([]),
            'lowStock' => $productRepository->countLowStock(),
            'outOfStock' => $productRepository->countOutOfStock(),
        ]);
    }

    #[Route('/products/new', name: 'app_admin_product_new')]
    public function newProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $product->setCreatedBy($this->getUser());

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Product "%s" added to inventory!', $product->getName()));
            return $this->redirectToRoute('app_admin_products');
        }

        return $this->render('admin/products/form.html.twig', [
            'productForm' => $form->createView(),
            'isEdit' => false,
        ]);
    }

    #[Route('/products/{id}/edit', name: 'app_admin_product_edit', requirements: ['id' => '\d+'])]
    public function editProduct(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', sprintf('Product "%s" updated successfully!', $product->getName()));
            return $this->redirectToRoute('app_admin_products');
        }

        return $this->render('admin/products/form.html.twig', [
            'productForm' => $form->createView(),
            'product' => $product,
            'isEdit' => true,
        ]);
    }

    #[Route('/products/{id}/quick-stock', name: 'app_admin_product_quick_stock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function quickStock(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('quick_stock_' . $product->getId(), $request->request->get('_token'))) {
            $addStock = (int) $request->request->get('add_stock', 1);
            $newStock = max(0, $product->getStock() + $addStock);
            $product->setStock($newStock);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Stock for "%s" updated to %d.', $product->getName(), $newStock));
        }

        return $this->redirectToRoute('app_admin_products');
    }

    #[Route('/products/{id}/delete', name: 'app_admin_product_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteProduct(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            $productName = $product->getName();
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Product "%s" deleted successfully.', $productName));
        } else {
            $this->addFlash('danger', 'Invalid deletion token.');
        }

        return $this->redirectToRoute('app_admin_products');
    }

    // ==========================================
    // 4. ORDER MANAGEMENT
    // ==========================================
    #[Route('/orders', name: 'app_admin_orders')]
    public function orders(Request $request, OrderRepository $orderRepository): Response
    {
        $search = $request->query->get('search');
        $status = $request->query->get('status');

        $orders = $orderRepository->searchOrders($search, $status);

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'totalOrders' => $orderRepository->count([]),
            'processingOrders' => $orderRepository->countByStatus('Processing'),
            'approvedOrders' => $orderRepository->countByStatus('Approved'),
            'shippedOrders' => $orderRepository->countByStatus('Shipped'),
            'deliveredOrders' => $orderRepository->countByStatus('Delivered'),
            'cancelledOrders' => $orderRepository->countByStatus('Cancelled'),
        ]);
    }

    #[Route('/orders/{id}/status', name: 'app_admin_order_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateOrderStatus(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('order_status_' . $order->getId(), $request->request->get('_token'))) {
            // Cancelled orders cannot be re-opened — they are locked
            if ($order->getStatus() === 'Cancelled') {
                $this->addFlash('danger', 'Order #' . $order->getOrderNumber() . ' was cancelled by the customer and cannot be edited.');
                return $this->redirectToRoute('app_admin_orders');
            }

            $newStatus = $request->request->get('status');
            $validStatuses = ['Processing', 'Approved', 'Shipped', 'Delivered'];

            if (in_array($newStatus, $validStatuses, true)) {
                $order->setStatus($newStatus);
                $entityManager->flush();
                $this->addFlash('success', sprintf('Order #%s status changed to %s.', $order->getOrderNumber(), $newStatus));
            }
        }

        return $this->redirectToRoute('app_admin_orders');
    }

    #[Route('/orders/{id}/delete', name: 'app_admin_order_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteOrder(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_order_' . $order->getId(), $request->request->get('_token'))) {
            $orderNumber = $order->getOrderNumber();
            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Order #%s deleted successfully.', $orderNumber));
        }

        return $this->redirectToRoute('app_admin_orders');
    }

    // ==========================================
    // 5. MESSAGES / INQUIRIES
    // ==========================================
    #[Route('/messages', name: 'app_admin_messages')]
    public function messages(MessageRepository $messageRepository): Response
    {
        $messages = $messageRepository->findAllOrdered();

        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages,
            'unreadCount' => $messageRepository->countUnread(),
            'totalMessages' => count($messages),
        ]);
    }

    #[Route('/messages/{id}/read', name: 'app_admin_message_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markMessageRead(Message $message, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('read_msg_' . $message->getId(), $request->request->get('_token'))) {
            $message->setStatus('read');
            $entityManager->flush();
            $this->addFlash('info', 'Message marked as read.');
        }

        return $this->redirectToRoute('app_admin_messages');
    }

    #[Route('/messages/{id}/delete', name: 'app_admin_message_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteMessage(Message $message, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_msg_' . $message->getId(), $request->request->get('_token'))) {
            $entityManager->remove($message);
            $entityManager->flush();
            $this->addFlash('success', 'Message deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_messages');
    }
}
