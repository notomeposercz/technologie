<?php
/**
 * Formulář pro filtrování a vyhledávání
 * 
 * @author PrestaShop Module Generator
 * @copyright 2024
 * @license MIT
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulář pro filtrování seznamu technologií
 */
class FilterType extends AbstractType
{
    /**
     * Sestavení formuláře
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', SearchType::class, [
                'label' => 'Vyhledat',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Hledat podle názvu nebo popisu...',
                    'autocomplete' => 'off'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Stav',
                'choices' => [
                    'Všechny' => '',
                    'Aktivní' => '1',
                    'Neaktivní' => '0'
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('sort_by', ChoiceType::class, [
                'label' => 'Řadit podle',
                'choices' => [
                    'Pořadí' => 'position',
                    'Názvu (A-Z)' => 'name_asc',
                    'Názvu (Z-A)' => 'name_desc',
                    'Datum vytvoření (nejnovější)' => 'date_add_desc',
                    'Datum vytvoření (nejstarší)' => 'date_add_asc',
                    'Datum aktualizace' => 'date_upd_desc'
                ],
                'required' => false,
                'data' => 'position', // Výchozí řazení
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('per_page', ChoiceType::class, [
                'label' => 'Položek na stránku',
                'choices' => [
                    '10' => 10,
                    '25' => 25,
                    '50' => 50,
                    '100' => 100
                ],
                'required' => false,
                'data' => 25, // Výchozí počet
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('filter', SubmitType::class, [
                'label' => 'Filtrovat',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
            ->add('reset', ResetType::class, [
                'label' => 'Vymazat',
                'attr' => [
                    'class' => 'btn btn-secondary',
                    'onclick' => 'window.location.href = window.location.pathname; return false;'
                ]
            ]);
    }

    /**
     * Konfigurace možností formuláře
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false, // Pro filtrování není CSRF potřeba
            'method' => 'GET',
            'allow_extra_fields' => true // Povolíme extra pole jako page
        ]);
    }

    /**
     * Prefix pro formulář
     */
    public function getBlockPrefix(): string
    {
        return 'filter';
    }
}
