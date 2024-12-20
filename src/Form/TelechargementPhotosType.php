<?php

namespace App\Form;

use App\Entity\Edition;
use App\Entity\Equipesadmin;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use App\Entity\Photos;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TelechargementPhotosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $listePhotos = $options['listePhotos'];

        if ($listePhotos != null) {

            foreach ($listePhotos as $photo) {
                $builder
                    ->add('check' . $photo->getId(), CheckboxType::class,
                        ['label' => '',
                            'required' => false,
                            'attr' => ['class' => 'form-check-input', 'id' => 'check' . $photo->getId(), 'name' => 'check' . $photo->getId()]]);
            }
            $builder->add('valider', SubmitType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'listePhotos' => null
        ]);
    }
}
