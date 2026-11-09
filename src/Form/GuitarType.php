<?php

namespace App\Form;

use App\Entity\Guitar;
use App\Enum\Availability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * The add / edit product form (same idea as the professor's ProductType).
 * Each field is drawn by hand in templates/product/_form_fields.html.twig.
 */
class GuitarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // empty_data: an empty box becomes '' instead of null, so the
            // "Enter the product name." message shows instead of an error page
            ->add('name', TextType::class, ['empty_data' => ''])
            ->add('brand', TextType::class, ['empty_data' => ''])
            ->add('itemCode', TextType::class, ['empty_data' => ''])
            ->add('category', ChoiceType::class, [
                'choices' => array_combine(Guitar::CATEGORIES, Guitar::CATEGORIES),
                'placeholder' => 'Choose a category',
                'empty_data' => '',
            ])
            ->add('price', NumberType::class, [
                'scale' => 2,
                'input' => 'string',
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0'],
            ])
            ->add('stock', IntegerType::class, [
                'empty_data' => '0',
                'attr' => ['min' => '0'],
            ])
            ->add('availability', EnumType::class, [
                'class' => Availability::class,
                'choice_label' => fn (Availability $a): string => $a->label(),
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('isBestseller', CheckboxType::class, ['required' => false])
            ->add('isNew', CheckboxType::class, ['required' => false])
            // Not saved directly on the Guitar: the controller moves the file
            // into the store's uploads/guitars/ folder and saves its path.
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '4M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Upload a JPG, PNG or WEBP photo.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Guitar::class,
        ]);
    }
}
