<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
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
        return $this->redirectToRoute('app_staff_products');
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
}
