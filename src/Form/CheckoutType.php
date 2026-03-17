<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // L'ID de la carte sauvegardée
            ->add('selectedCardId', HiddenType::class, [
                'required' => false,
            ])
            // Les champs de la nouvelle carte
            ->add('cardNumber', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => '0000 0000 0000 0000', 'maxlength' => 19]
            ])
            ->add('expDate', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'MM/AA', 'maxlength' => 5]
            ])
            ->add('cvv', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => '123', 'maxlength' => 3]
            ])
            // La case à cocher pour sauvegarder la carte
            ->add('saveCard', CheckboxType::class, [
                'required' => false,
                'label' => 'Sauvegarder cette carte pour mes prochains achats'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        // Configure your form options here
    }
}
