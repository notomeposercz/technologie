<?php
/**
 * Formulář pro hromadné akce
 * 
 * @author PrestaShop Module Generator
 * @copyright 2024
 * @license MIT
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulář pro hromadné operace s technologiemi
 */
class BulkActionType extends AbstractType
{
    /**
     * Sestavení formuláře
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', ChoiceType::class, [
                'label' => 'Hromadná akce',
                'choices' => [
                    'Vyberte akci...' => '',
                    'Aktivovat vybrané' => 'activate',
                    'Deaktivovat vybrané' => 'deactivate',
                    'Smazat vybrané' => 'delete'
                ],
                'attr' => [
                    'class' => 'form-select',
                    'onchange' => 'this.form.submit()'
                ],
                'required' => false
            ])
            ->add('selected_ids', HiddenType::class, [
                'attr' => [
                    'id' => 'bulk_selected_ids'
                ]
            ]);
    }

    /**
     * Konfigurace možností formuláře
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'bulk_action_form',
            'method' => 'POST'
        ]);
    }

    /**
     * Prefix pro formulář
     */
    public function getBlockPrefix(): string
    {
        return 'bulk_action';
    }
}
