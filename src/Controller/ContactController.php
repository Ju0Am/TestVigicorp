<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ContactController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {

        return $this->render('contact/index.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contactform(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(ContactFormType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $newUser = $form->getData();

            $file = $form->get('file')->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                $file->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads',
                    $newFilename
                );

                $newUser->setFile($newFilename);
            }

            $em->persist($newUser);
            $em->flush();

            $email = (new TemplatedEmail())
                ->from('noreply@vigicorp.com')
                ->to('user@vigicorp.com')
                ->subject('Nouvelle soumission de contact')
                ->text('Nom : '.$user->getNom().', Prénom : '.$user->getPrenom().', Email : '.$user->getEmail());

            if ($file) {

                $email->attachFromPath($this->getParameter('kernel.project_dir').'/public/uploads/'.$newFilename);
            }

            $mailer->send($email);

            $this->addFlash('succes', 'enregistrement réussi');

            return $this->redirectToRoute('app_index');
        }

        return $this->render('contact/form.html.twig', [
            'contactForm' => $form,
        ]);
    }
}
