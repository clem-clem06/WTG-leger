<?php

namespace App\Controller;

use App\Entity\Offre;
use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\AddToCartType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
final class OfferController extends AbstractController
{
    #[Route('/offer/{id}', name: 'app_offer_show')]
    public function show(Offre $offre): Response
    {
        $form = $this->createForm(AddToCartType::class, null, [
            'action' => $this->generateUrl('app_cart_add', ['id' => $offre->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('offer/show.html.twig', [
            'offre' => $offre,
            'form' => $form->createView(),
        ]);
    }
}
