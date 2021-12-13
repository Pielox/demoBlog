<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {   

        if($options['commentFormBack'] == true)
        {
            $builder
            ->add('auteur', TextType::class, [
                'label' => "Nom",
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir votre nom.'
                    ])
                    ],
                'attr' => ["placeholder" => "Saissez votre nom"],
            ])
                
            ->add('commentaire', TextareaType::class, [
                'label' => "Commentaire",
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir votre commentaire.'
                    ])
                    ],
                'attr' => ["placeholder" => "Saissez votre commentaire"],
            ])
            ;
        }
        elseif($options['commentFormFront'] == true)
        {
            $builder
            ->add('commentaire', TextareaType::class, [
                'label' => "Commentaire",
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de saisir votre commentaire.'
                    ])
                    ],
                'attr' => ["placeholder" => "Saissez votre commentaire"],
            ])
            ;
        }
    }
      

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
            'commentFormFront' => false,
            'commentFormBack' => false
        ]);
    }
}
