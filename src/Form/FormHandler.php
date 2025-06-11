<?php
/**
 * Handler pro zpracování formulářů
 * 
 * @author PrestaShop Module Generator
 * @copyright 2024
 * @license MIT
 */

declare(strict_types=1);

namespace PrestaShop\Module\Technologie\Form;

use PrestaShop\Module\Technologie\Entity\Technologie;
use PrestaShop\Module\Technologie\Repository\TechnologieRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Třída pro zpracování formulářových dat
 */
class FormHandler
{
    private TechnologieRepository $repository;
    private FileUploadHandler $fileUploadHandler;
    private FormValidator $validator;

    /**
     * Konstruktor
     */
    public function __construct(
        TechnologieRepository $repository,
        FileUploadHandler $fileUploadHandler,
        FormValidator $validator
    ) {
        $this->repository = $repository;
        $this->fileUploadHandler = $fileUploadHandler;
        $this->validator = $validator;
    }

    /**
     * Zpracování formuláře pro vytvoření technologie
     * 
     * @param FormInterface $form Formulář
     * @return array Výsledek zpracování [success => bool, technologie => Technologie|null, errors => array]
     */
    public function handleCreateForm(FormInterface $form): array
    {
        if (!$form->isSubmitted() || !$form->isValid()) {
            return [
                'success' => false,
                'technologie' => null,
                'errors' => $this->getFormErrors($form)
            ];
        }

        try {
            /** @var Technologie $technologie */
            $technologie = $form->getData();
            
            // Zpracování upload obrázku
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $filename = $this->fileUploadHandler->uploadImage($imageFile);
                $technologie->setImage($filename);
            }

            // Nastavení výchozích hodnot
            if ($technologie->getPosition() === null) {
                $maxPosition = $this->repository->getMaxPosition();
                $technologie->setPosition($maxPosition + 1);
            }

            // Uložení do databáze
            $this->repository->save($technologie);

            return [
                'success' => true,
                'technologie' => $technologie,
                'errors' => []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'technologie' => null,
                'errors' => ['Chyba při ukládání: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Zpracování formuláře pro editaci technologie
     * 
     * @param FormInterface $form Formulář
     * @param Technologie $technologie Editovaná technologie
     * @return array Výsledek zpracování
     */
    public function handleEditForm(FormInterface $form, Technologie $technologie): array
    {
        if (!$form->isSubmitted() || !$form->isValid()) {
            return [
                'success' => false,
                'technologie' => $technologie,
                'errors' => $this->getFormErrors($form)
            ];
        }

        try {
            $oldImage = $technologie->getImage();
            
            // Zpracování upload nového obrázku
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $filename = $this->fileUploadHandler->uploadImage($imageFile);
                $technologie->setImage($filename);
                
                // Smazání starého obrázku
                if ($oldImage) {
                    $this->fileUploadHandler->deleteImage($oldImage);
                }
            }

            // Uložení změn
            $this->repository->save($technologie);

            return [
                'success' => true,
                'technologie' => $technologie,
                'errors' => []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'technologie' => $technologie,
                'errors' => ['Chyba při ukládání: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Zpracování hromadných akcí
     * 
     * @param string $action Akce k provedení
     * @param array $ids Pole ID technologií
     * @return array Výsledek zpracování
     */
    public function handleBulkAction(string $action, array $ids): array
    {
        $errors = $this->validator->validateBulkAction($action, $ids);
        if (!empty($errors)) {
            return [
                'success' => false,
                'processed' => 0,
                'errors' => $errors
            ];
        }

        try {
            $processed = 0;

            switch ($action) {
                case 'activate':
                    $processed = $this->repository->bulkUpdateActive($ids, true);
                    break;

                case 'deactivate':
                    $processed = $this->repository->bulkUpdateActive($ids, false);
                    break;

                case 'delete':
                    $processed = $this->bulkDelete($ids);
                    break;
            }

            return [
                'success' => true,
                'processed' => $processed,
                'errors' => []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'processed' => 0,
                'errors' => ['Chyba při zpracování: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Hromadné smazání technologií
     * 
     * @param array $ids Pole ID k smazání
     * @return int Počet smazaných záznamů
     */
    private function bulkDelete(array $ids): int
    {
        $processed = 0;

        foreach ($ids as $id) {
            $technologie = $this->repository->findOneById((int) $id);
            if ($technologie) {
                // Smazání obrázku
                if ($technologie->getImage()) {
                    $this->fileUploadHandler->deleteImage($technologie->getImage());
                }
                
                // Smazání záznamu
                $this->repository->delete($technologie);
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Získání chyb z formuláře
     * 
     * @param FormInterface $form Formulář
     * @return array Pole chyb
     */
    private function getFormErrors(FormInterface $form): array
    {
        $errors = [];

        // Chyby formuláře
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        // Chyby polí
        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $fieldName = $child->getName();
                foreach ($child->getErrors() as $error) {
                    $errors[$fieldName] = $error->getMessage();
                }
            }
        }

        return $errors;
    }
}
