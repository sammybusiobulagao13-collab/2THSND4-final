<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        $message = new Message();

        // Pre-fill user data if authenticated
        if ($this->getUser()) {
            $message->setName($this->getUser()->getName());
            $message->setEmail($this->getUser()->getEmail());
        }

        $form = $this->createForm(ContactType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser()) {
                $message->setName($this->getUser()->getName());
                $message->setEmail($this->getUser()->getEmail());
            }

            $entityManager->persist($message);
            $entityManager->flush();

            $this->addFlash('success', 'Thank you! Your message has been sent to the 2THSND4 team.');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}
