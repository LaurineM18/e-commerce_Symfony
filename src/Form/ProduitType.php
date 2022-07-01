<?php

namespace App\Form;

use App\Entity\Produit;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('couleur', ColorType::class)
            ->add('taille')
            ->add('collection', ChoiceType::class , [
                "choices" => [
                    "femme" => "f",
                    "homme" => "m"
                ],
                "placeholder" => "--choisir--"
            ])
            ->add('photo', FileType::class, ["mapped"=> false])
            ->add('prix', MoneyType::class)
            ->add('stock')
            //->add('date_enregistrement')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
