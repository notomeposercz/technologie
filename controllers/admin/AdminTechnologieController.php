<?php
/**
 * Admin controller pro správu technologií - PRODUKČNÍ VERZE
 */

// Načtení autoloaderu modulu
require_once _PS_MODULE_DIR_ . 'technologie/technologie.php';

use PrestaShop\Module\Technologie\Entity\Technologie as TechnologieEntity;
use PrestaShop\Module\Technologie\Repository\TechnologieRepository;
use PrestaShop\Module\Technologie\Repository\TechnologieDbRepository;
use PrestaShop\Module\Technologie\Form\TechnologieType;
use PrestaShop\Module\Technologie\Form\BulkActionType;
use PrestaShop\Module\Technologie\Form\FileUploadHandler;

class AdminTechnologieController extends ModuleAdminController
{
    private $technologieRepository = null;
    private $fileUploadHandler = null;

    public function __construct($bootstrap = true, $display = true)
    {
        $this->bootstrap = true;
        $this->table = 'technologie';
        $this->className = 'TechnologieModel';
        $this->lang = false;
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        parent::__construct($bootstrap, $display);

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Smazat vybrané'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Opravdu chcete smazat vybrané technologie?')
            ],
            'enableSelection' => [
                'text' => $this->l('Aktivovat vybrané'),
                'icon' => 'icon-power-off text-success'
            ],
            'disableSelection' => [
                'text' => $this->l('Deaktivovat vybrané'),
                'icon' => 'icon-power-off text-danger'
            ]
        ];
    }

    private function getTechnologieRepository()
    {
        if ($this->technologieRepository === null) {
            try {
                if (method_exists($this, 'get') && $this->get('doctrine.orm.entity_manager')) {
                    $entityManager = $this->get('doctrine.orm.entity_manager');
                    $this->technologieRepository = $entityManager->getRepository(TechnologieEntity::class);
                } else {
                    throw new \Exception('Doctrine není dostupné');
                }
            } catch (\Exception $e) {
                $this->technologieRepository = new TechnologieDbRepository();
            }
        }
        return $this->technologieRepository;
    }

    private function getFileUploadHandler()
    {
        if ($this->fileUploadHandler === null) {
            $this->fileUploadHandler = new FileUploadHandler();
        }
        return $this->fileUploadHandler;
    }

    private function ensureUploadDirectory(): ?string
    {
        $uploadDir = _PS_MODULE_DIR_ . 'technologie/uploads/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $this->errors[] = $this->l('Nelze vytvořit upload adresář');
                return null;
            }
        }

        if (!is_writable($uploadDir)) {
            $this->errors[] = $this->l('Upload adresář není zapisovatelný');
            return null;
        }

        $htaccessPath = $uploadDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "Options -Indexes\n";
            $htaccessContent .= "RedirectMatch 403 \.php$\n";
            file_put_contents($htaccessPath, $htaccessContent);
        }

        return $uploadDir;
    }

    private function validateUploadedFile(array $file): bool
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if ($file['size'] > $maxSize) {
            $this->errors[] = $this->l('Soubor je příliš velký. Maximální velikost je 2MB');
            return false;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                $this->errors[] = $this->l('Nepovolený typ souboru. Povolené: JPG, PNG, GIF, WebP');
                return false;
            }
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $this->errors[] = $this->l('Soubor není platný obrázek');
            return false;
        }

        return true;
    }

    private function generateSecureFilename(array $file): string
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (empty($extension)) {
            $mimeExtensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png', 
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $extension = $mimeExtensions[$mimeType] ?? 'jpg';
        }

        return 'tech_' . time() . '_' . uniqid() . '.' . $extension;
    }

    private function handleImageUpload(array $file)
    {
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[] = $this->l('Chyba při nahrávání souboru');
                return null;
            }

            if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
                $this->errors[] = $this->l('Dočasný soubor není dostupný');
                return null;
            }

            if (!$this->validateUploadedFile($file)) {
                return null;
            }

            $uploadDir = $this->ensureUploadDirectory();
            if (!$uploadDir) {
                return null;
            }

            $filename = $this->generateSecureFilename($file);
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                chmod($targetPath, 0644);
                
                if (file_exists($targetPath) && is_readable($targetPath)) {
                    return $filename;
                } else {
                    $this->errors[] = $this->l('Soubor byl nahrán, ale není přístupný');
                    return null;
                }
            } else {
                $this->errors[] = $this->l('Nepodařilo se přesunout nahraný soubor');
                return null;
            }

        } catch (\Exception $e) {
            $this->errors[] = $this->l('Chyba při zpracování obrázku: ') . $e->getMessage();
            return null;
        }
    }

    private function processFormSubmission($technologie = null)
    {
        // Rozšířená detekce submit tlačítka
        $isSubmitted = false;
        $possibleSubmitKeys = [
            'submitAddtechnologie',
            'submitAdd' . $this->table,
            'submitAdd',
            'submit'
        ];
        
        foreach ($possibleSubmitKeys as $key) {
            if (isset($_POST[$key])) {
                $isSubmitted = true;
                break;
            }
        }
        
        // Alternativní detekce - pokud je POST a obsahuje povinná pole
        if (!$isSubmitted && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['name']) && !empty($_POST['name'])) {
                $isSubmitted = true;
            }
        }
        
        if (!$isSubmitted) {
            return false;
        }

        try {
            $name = Tools::getValue('name', '');
            $description = Tools::getValue('description', '');
            $position = (int)Tools::getValue('position', 0);
            $active = Tools::getValue('active') ? true : false;

            if (empty($name)) {
                $this->errors[] = $this->l('Název technologie je povinný');
                return false;
            }

            if ($this->id_object && $technologie && method_exists($technologie, 'setName')) {
                $editTechnologie = $technologie;
            } else {
                $editTechnologie = new TechnologieEntity();
            }

            $editTechnologie->setName($name);
            $editTechnologie->setDescription($description);
            $editTechnologie->setActive($active);

            if ($position <= 0) {
                try {
                    $position = $this->getTechnologieRepository()->getMaxPosition() + 1;
                } catch (\Exception $e) {
                    $position = 1;
                }
            }
            $editTechnologie->setPosition($position);

            // Zpracování obrázku
            $currentImage = '';
            if ($this->id_object && method_exists($editTechnologie, 'getImage')) {
                $currentImage = $editTechnologie->getImage() ?? '';
            }

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $newFilename = $this->handleImageUpload($_FILES['image']);
                
                if ($newFilename) {
                    if ($currentImage && $currentImage !== $newFilename) {
                        $this->deleteImageFile($currentImage);
                    }
                    
                    $editTechnologie->setImage($newFilename);
                } else {
                    if ($currentImage) {
                        $editTechnologie->setImage($currentImage);
                    }
                    return false;
                }
            } else {
                if ($currentImage) {
                    $editTechnologie->setImage($currentImage);
                }
            }

            // Uložení do databáze
            $this->getTechnologieRepository()->save($editTechnologie);
            
            return true;

        } catch (\Exception $e) {
            $this->errors[] = $this->l('Chyba při ukládání: ') . $e->getMessage();
            return false;
        }
    }

    private function deleteImageFile(string $filename): bool
    {
        if (empty($filename)) {
            return true;
        }

        $uploadDir = _PS_MODULE_DIR_ . 'technologie/uploads/';
        $filePath = $uploadDir . $filename;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }

    private function prepareTechnologieData($technologie): array
    {
        if ($this->id_object && $technologie && method_exists($technologie, 'getName')) {
            $imageUrl = '';
            if ($technologie->getImage()) {
                $imageUrl = _MODULE_DIR_ . 'technologie/uploads/' . $technologie->getImage();
            }

            return [
                'name' => $technologie->getName(),
                'description' => $technologie->getDescription(),
                'image' => $technologie->getImage(),
                'position' => $technologie->getPosition(),
                'active' => $technologie->isActive() ? 1 : 0,
                'image_url' => $imageUrl
            ];
        } else {
            return [
                'name' => '',
                'description' => '',
                'image' => '',
                'position' => 0,
                'active' => 1,
                'image_url' => ''
            ];
        }
    }

    public function renderForm()
    {
        // Načtení entity pro editaci
        $technologie = null;
        if ($this->id_object) {
            try {
                $technologie = $this->getTechnologieRepository()->findOneById((int)$this->id_object);
                if (!$technologie) {
                    $this->errors[] = $this->l('Technologie nebyla nalezena');
                    return $this->renderList();
                }
            } catch (\Exception $e) {
                $this->errors[] = $this->l('Chyba při načítání technologie: ') . $e->getMessage();
                return $this->renderList();
            }
        }

        // Zpracování POST požadavku
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->processFormSubmission($technologie)) {
                $this->confirmations[] = $this->l('Technologie byla úspěšně uložena');
                
                if (empty($this->errors)) {
                    $adminLink = $this->context->link->getAdminLink('AdminTechnologie');
                    Tools::redirectAdmin($adminLink);
                }
            }
        }

        // Příprava dat pro šablonu
        $technologieData = $this->prepareTechnologieData($technologie);

        $this->context->smarty->assign([
            'technologie' => $technologieData,
            'is_edit' => (bool)$this->id_object,
            'table' => $this->table,
            'upload_dir' => _MODULE_DIR_ . 'technologie/uploads/',
            'back_url' => $this->context->link->getAdminLink('AdminTechnologie'),
            'errors' => $this->errors
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'technologie/views/templates/admin/form.tpl');
    }

    public function renderList()
    {
        $this->addCSS(_MODULE_DIR_ . 'technologie/views/css/admin.css');
        $this->addJS(_MODULE_DIR_ . 'technologie/views/js/admin.js');

        $this->fields_list = [
            'id_technologie' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Název'),
                'width' => 'auto'
            ],
            'description' => [
                'title' => $this->l('Popis'),
                'width' => 'auto',
                'maxlength' => 100
            ],
            'image' => [
                'title' => $this->l('Obrázek'),
                'align' => 'center',
                'callback' => 'displayImage',
                'orderby' => false,
                'search' => false
            ],
            'position' => [
                'title' => $this->l('Pořadí'),
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ],
            'active' => [
                'title' => $this->l('Stav'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-sm'
            ],
            'date_add' => [
                'title' => $this->l('Vytvořeno'),
                'align' => 'center',
                'type' => 'datetime',
                'class' => 'fixed-width-lg'
            ]
        ];

        return parent::renderList();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        try {
            $technologie = $this->getTechnologieRepository()->findAllOrderedByPosition();

            $list = [];
            foreach ($technologie as $tech) {
                $list[] = [
                    'id_technologie' => $tech->getId(),
                    'name' => $tech->getName(),
                    'description' => $tech->getDescription(),
                    'image' => $tech->getImage(),
                    'position' => $tech->getPosition(),
                    'active' => $tech->isActive(),
                    'date_add' => $tech->getDateAdd()->format('Y-m-d H:i:s')
                ];
            }

            $this->_list = $list;
            $this->_listTotal = count($list);

            return $list;

        } catch (\Exception $e) {
            return $this->getListFromDatabase($id_lang, $order_by, $order_way, $start, $limit);
        }
    }

    private function getListFromDatabase($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'technologie` ORDER BY position ASC, name ASC';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$start . ', ' . (int)$limit;
        }

        $results = Db::getInstance()->executeS($sql);

        if (!$results) {
            $results = [];
        }

        $formattedResults = [];
        foreach ($results as $row) {
            $formattedResults[] = [
                'id_technologie' => $row['id_technologie'],
                'name' => $row['name'],
                'description' => $row['description'],
                'image' => $row['image'],
                'position' => $row['position'],
                'active' => $row['active'],
                'date_add' => $row['date_add']
            ];
        }

        $this->_list = $formattedResults;
        $this->_listTotal = count($formattedResults);

        return $formattedResults;
    }

    public function displayImage($value, $row)
    {
        if (empty($value)) {
            return '<span class="text-muted">Bez obrázku</span>';
        }

        $imageUrl = _MODULE_DIR_ . 'technologie/uploads/' . $value;
        return '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($row['name']) . '"
                style="max-width: 50px; max-height: 50px; object-fit: cover;"
                class="img-thumbnail">';
    }

    // Bulk action metody
    public function processBulkDelete()
    {
        $ids = Tools::getValue('technologieBox');
        if (!is_array($ids) || empty($ids)) {
            $this->errors[] = $this->l('Nevybrali jste žádné technologie');
            return;
        }

        try {
            foreach ($ids as $id) {
                $technologie = $this->getTechnologieRepository()->findOneById((int)$id);
                if ($technologie) {
                    if ($technologie->getImage()) {
                        $this->deleteImageFile($technologie->getImage());
                    }
                    $this->getTechnologieRepository()->delete($technologie);
                }
            }
            $this->confirmations[] = sprintf($this->l('Bylo smazáno %d technologií'), count($ids));
        } catch (\Exception $e) {
            $this->errors[] = $this->l('Chyba při mazání: ') . $e->getMessage();
        }
    }

    public function processBulkEnableSelection()
    {
        $this->processBulkStatus(true);
    }

    public function processBulkDisableSelection()
    {
        $this->processBulkStatus(false);
    }

    private function processBulkStatus($status)
    {
        $ids = Tools::getValue('technologieBox');
        if (!is_array($ids) || empty($ids)) {
            $this->errors[] = $this->l('Nevybrali jste žádné technologie');
            return;
        }

        try {
            $this->getTechnologieRepository()->bulkUpdateActive($ids, $status);
            $action = $status ? $this->l('aktivováno') : $this->l('deaktivováno');
            $this->confirmations[] = sprintf($this->l('Bylo %s %d technologií'), $action, count($ids));
        } catch (\Exception $e) {
            $this->errors[] = $this->l('Chyba při změně stavu: ') . $e->getMessage();
        }
    }

    public function processDelete()
    {
        if (!$this->id_object) {
            $this->errors[] = $this->l('Neplatné ID technologie');
            return;
        }

        try {
            $technologie = $this->getTechnologieRepository()->findOneById((int)$this->id_object);
            if (!$technologie) {
                $this->errors[] = $this->l('Technologie nebyla nalezena');
                return;
            }

            if ($technologie->getImage()) {
                $this->deleteImageFile($technologie->getImage());
            }

            $this->getTechnologieRepository()->delete($technologie);
            $this->confirmations[] = $this->l('Technologie byla úspěšně smazána');

        } catch (\Exception $e) {
            $this->errors[] = $this->l('Chyba při mazání: ') . $e->getMessage();
        }
    }

    public function ajaxProcessUpdatePositions()
    {
        if (!Tools::isSubmit('positions')) {
            die(json_encode(['success' => false, 'message' => $this->l('Chybí data pozic')]));
        }

        try {
            $positions = json_decode(Tools::getValue('positions'), true);
            $this->getTechnologieRepository()->updatePositions($positions);
            die(json_encode(['success' => true, 'message' => $this->l('Pořadí bylo aktualizováno')]));
        } catch (\Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
}