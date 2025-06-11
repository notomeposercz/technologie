<?php
/**
 * Symfony formulář pro technologie
 * 
 * @author PrestaShop Module Generator
 * @copyright 2024
 * @license MIT
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Form;

use PrestaShop\Module\Technologie\Entity\Technologie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulář pro vytváření a editaci technologií
 */
class TechnologieType extends AbstractType
{
    /**
     * Sestavení formuláře
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Název technologie',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Zadejte název technologie',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Název technologie je povinný'
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Název může mít maximálně {{ limit }} znaků'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Popis technologie',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Zadejte popis technologie',
                    'rows' => 4
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Popis může mít maximálně {{ limit }} znaků'
                    ])
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Obrázek technologie',
                'required' => false,
                'mapped' => false, // Nebude automaticky mapováno na entitu
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Nahrajte platný obrázek (JPG, PNG, GIF, WebP)',
                        'maxSizeMessage' => 'Obrázek může mít maximálně {{ limit }}'
                    ])
                ]
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Pořadí',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Zadejte pořadí (0 = první)',
                    'min' => 0
                ],
                'constraints' => [
                    new Assert\PositiveOrZero([
                        'message' => 'Pořadí musí být kladné číslo nebo nula'
                    ])
                ]
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Aktivní',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);
    }

    /**
     * Konfigurace možností formuláře
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Technologie::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'technologie_form'
        ]);
    }

    /**
     * Prefix pro formulář
     */
    public function getBlockPrefix(): string
    {
        return 'technologie';
    }
}
