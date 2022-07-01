<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Membre;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite')
            ->add('montant', MoneyType::class)
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'en cours de traitement' => 'En cours de traitement',
                    'envoye' => 'envoye',
                    'livre' => 'livre'
                ]
            ])
            //->add('date_enregistrement')
            ->add('membre', EntityType::class , [
                'class' => Membre::class,
                'choice_label' => 'pseudo',
            ])
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => "titre"
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
