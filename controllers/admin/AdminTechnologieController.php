<?php
/**
 * Admin controller pro správu technologií - OPRAVENÁ VERZE
 * Problém: Nesprávná detekce submit tlačítka
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

    /**
     * Debug funkce pro logování
     */
    private function debugLog($message, $data = null) {
        $logFile = _PS_MODULE_DIR_ . 'technologie/debug_upload.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message";
        
        if ($data !== null) {
            $logMessage .= " | Data: " . print_r($data, true);
        }
        
        $logMessage .= "\n" . str_repeat('-', 80) . "\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
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
        $this->debugLog("=== validateUploadedFile START ===");
        $this->debugLog("Validace souboru", $file);
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if ($file['size'] > $maxSize) {
            $this->debugLog("CHYBA: Soubor příliš velký: " . $file['size'] . " > $maxSize");
            $this->errors[] = $this->l('Soubor je příliš velký. Maximální velikost je 2MB');
            return false;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $this->debugLog("Detekovaný MIME typ: $mimeType");

            if (!in_array($mimeType, $allowedTypes)) {
                $this->debugLog("CHYBA: Nepovolený MIME typ: $mimeType");
                $this->errors[] = $this->l('Nepovolený typ souboru. Povolené: JPG, PNG, GIF, WebP');
                return false;
            }
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $this->debugLog("CHYBA: getimagesize() vrátilo false");
            $this->errors[] = $this->l('Soubor není platný obrázek');
            return false;
        }

        $this->debugLog("getimagesize OK: " . $imageInfo[0] . "x" . $imageInfo[1]);
        $this->debugLog("=== validateUploadedFile END - SUCCESS ===");
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
        $this->debugLog("=== handleImageUpload START ===");
        $this->debugLog("Input file data", $file);
        
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->debugLog("Upload error code: " . $file['error']);
                $this->errors[] = $this->l('Chyba při nahrávání souboru');
                return null;
            }

            if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
                $this->debugLog("CHYBA: tmp_name neexistuje nebo není čitelný: " . $file['tmp_name']);
                $this->errors[] = $this->l('Dočasný soubor není dostupný');
                return null;
            }

            $this->debugLog("tmp_name OK, velikost: " . filesize($file['tmp_name']) . " bytes");

            if (!$this->validateUploadedFile($file)) {
                $this->debugLog("Validace souboru selhala");
                return null;
            }

            $this->debugLog("Validace souboru prošla");

            $uploadDir = $this->ensureUploadDirectory();
            if (!$uploadDir) {
                $this->debugLog("CHYBA: Nepodařilo se zajistit upload adresář");
                return null;
            }

            $this->debugLog("Upload adresář OK: $uploadDir");

            $filename = $this->generateSecureFilename($file);
            $targetPath = $uploadDir . $filename;

            $this->debugLog("Generovaný název: $filename");
            $this->debugLog("Cílová cesta: $targetPath");

            $this->debugLog("Pokus o move_uploaded_file z '" . $file['tmp_name'] . "' do '$targetPath'");
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->debugLog("move_uploaded_file ÚSPĚŠNÝ");
                
                chmod($targetPath, 0644);
                $this->debugLog("chmod 644 nastaven");
                
                if (file_exists($targetPath) && is_readable($targetPath)) {
                    $actualSize = filesize($targetPath);
                    $this->debugLog("ÚSPĚCH: Soubor existuje a je čitelný, velikost: $actualSize bytes");
                    $this->debugLog("=== handleImageUpload END - SUCCESS ===");
                    return $filename;
                } else {
                    $this->debugLog("CHYBA: Soubor byl přesunut, ale není přístupný");
                    $this->errors[] = $this->l('Soubor byl nahrán, ale není přístupný');
                    return null;
                }
            } else {
                $this->debugLog("CHYBA: move_uploaded_file SELHAL");
                $this->errors[] = $this->l('Nepodařilo se přesunout nahraný soubor');
                return null;
            }

        } catch (\Exception $e) {
            $this->debugLog("=== VÝJIMKA V handleImageUpload ===");
            $this->debugLog("Chyba: " . $e->getMessage());
            
            $this->errors[] = $this->l('Chyba při zpracování obrázku: ') . $e->getMessage();
            return null;
        }
    }

    /**
     * OPRAVENÁ VERZE - Lepší detekce submit tlačítka
     */
    private function processFormSubmission($technologie = null)
    {
        $this->debugLog("=== ZAČÁTEK processFormSubmission ===");
        
        // ROZŠÍŘENÁ DETEKCE SUBMIT TLAČÍTKA
        $isSubmitted = false;
        $submitKeys = [];
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'submit') === 0) {
                $submitKeys[] = "$key = $value";
                $isSubmitted = true;
            }
        }
        
        $this->debugLog("POST keys obsahující 'submit'", $submitKeys);
        $this->debugLog("Je formulář odeslán? " . ($isSubmitted ? 'ANO' : 'NE'));
        
        // Pokud není detekován submit, ukončíme
        if (!$isSubmitted) {
            $this->debugLog("Formulář nebyl odeslán - končím");
            return false;
        }
        
        try {
            $this->debugLog("POST data", $_POST);
            $this->debugLog("FILES data", $_FILES);
            
            $name = Tools::getValue('name');
            $description = Tools::getValue('description', '');
            $position = (int)Tools::getValue('position');
            $active = Tools::getValue('active') ? true : false;

            $this->debugLog("Zpracovaná data", [
                'name' => $name,
                'description' => $description,
                'position' => $position,
                'active' => $active
            ]);

            if (empty($name)) {
                $this->errors[] = $this->l('Název technologie je povinný');
                $this->debugLog("CHYBA: Prázdný název");
                return false;
            }

            if ($this->id_object && $technologie && method_exists($technologie, 'setName')) {
                $editTechnologie = $technologie;
                $this->debugLog("EDITACE existující technologie ID: " . $this->id_object);
            } else {
                $editTechnologie = new TechnologieEntity();
                $this->debugLog("VYTVÁŘENÍ nové technologie");
            }

            $editTechnologie->setName($name);
            $editTechnologie->setDescription($description);
            $editTechnologie->setActive($active);

            if ($position <= 0) {
                try {
                    $position = $this->getTechnologieRepository()->getMaxPosition() + 1;
                    $this->debugLog("Automatická pozice nastavena na: $position");
                } catch (\Exception $e) {
                    $position = 1;
                    $this->debugLog("Chyba při získávání max pozice, nastaveno na 1: " . $e->getMessage());
                }
            }
            $editTechnologie->setPosition($position);

            $this->debugLog("=== ZPRACOVÁNÍ OBRÁZKU ===");
            
            $currentImage = '';
            if ($this->id_object && method_exists($editTechnologie, 'getImage')) {
                $currentImage = $editTechnologie->getImage() ?? '';
                $this->debugLog("Současný obrázek: " . ($currentImage ?: 'žádný'));
            }

            $this->debugLog("Kontrola FILES image", isset($_FILES['image']) ? $_FILES['image'] : 'NENÍ NASTAVENO');

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $this->debugLog("UPLOAD: Zahajuji upload nového obrázku");
                
                $newFilename = $this->handleImageUpload($_FILES['image']);
                $this->debugLog("Výsledek handleImageUpload: " . ($newFilename ?: 'NULL/FALSE'));
                
                if ($newFilename) {
                    $this->debugLog("ÚSPĚCH: Nový obrázek nahrán: $newFilename");
                    
                    if ($currentImage && $currentImage !== $newFilename) {
                        $this->debugLog("Mazání starého obrázku: $currentImage");
                        $this->deleteImageFile($currentImage);
                    }
                    
                    $editTechnologie->setImage($newFilename);
                    $this->debugLog("Nový obrázek nastaven do entity: $newFilename");
                } else {
                    $this->debugLog("CHYBA: Upload se nezdařil, zachovávám starý obrázek");
                    if ($currentImage) {
                        $editTechnologie->setImage($currentImage);
                    }
                    return false;
                }
            } else {
                $this->debugLog("Žádný nový obrázek, zachovávám současný: " . ($currentImage ?: 'žádný'));
                if ($currentImage) {
                    $editTechnologie->setImage($currentImage);
                }
            }

            $this->debugLog("Entity před uložením", [
                'id' => method_exists($editTechnologie, 'getId') ? $editTechnologie->getId() : 'N/A',
                'name' => $editTechnologie->getName(),
                'image' => method_exists($editTechnologie, 'getImage') ? $editTechnologie->getImage() : 'N/A',
                'position' => $editTechnologie->getPosition(),
                'active' => $editTechnologie->isActive()
            ]);

            $this->debugLog("=== UKLÁDÁNÍ DO DATABÁZE ===");
            $this->getTechnologieRepository()->save($editTechnologie);
            $this->debugLog("Úspěšně uloženo do databáze");
            
            $this->debugLog("Entity po uložení", [
                'id' => method_exists($editTechnologie, 'getId') ? $editTechnologie->getId() : 'N/A',
                'name' => $editTechnologie->getName(),
                'image' => method_exists($editTechnologie, 'getImage') ? $editTechnologie->getImage() : 'N/A'
            ]);
            
            $this->debugLog("=== KONEC processFormSubmission - ÚSPĚCH ===");
            return true;

        } catch (\Exception $e) {
            $this->debugLog("=== VÝJIMKA V processFormSubmission ===");
            $this->debugLog("Chyba: " . $e->getMessage());
            
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

    /**
     * HLAVNÍ OPRAVA - Jednodušší a robustnější renderForm
     */
    public function renderForm()
    {
        $this->debugLog("=== ZAČÁTEK renderForm ===");
        $this->debugLog("id_object: " . $this->id_object);
        $this->debugLog("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        
        // Jednodušší detekce POST
        $isPostRequest = ($_SERVER['REQUEST_METHOD'] === 'POST');
        $this->debugLog("Je POST request? " . ($isPostRequest ? 'ANO' : 'NE'));
        
        // Načtení entity pro editaci
        $technologie = null;
        if ($this->id_object) {
            try {
                $technologie = $this->getTechnologieRepository()->findOneById((int)$this->id_object);
                if (!$technologie) {
                    $this->errors[] = $this->l('Technologie nebyla nalezena');
                    return $this->renderList();
                }
                $this->debugLog("Technologie pro editaci načtena: " . $technologie->getName());
            } catch (\Exception $e) {
                $this->errors[] = $this->l('Chyba při načítání technologie: ') . $e->getMessage();
                return $this->renderList();
            }
        }

        // Zpracování POST požadavku - BEZ PODMÍNEK NA SUBMIT KLÍČE
        if ($isPostRequest) {
            $this->debugLog("Zpracovávám POST formulář");
            if ($this->processFormSubmission($technologie)) {
                $this->confirmations[] = $this->l('Technologie byla úspěšně uložena');
                $this->debugLog("Formulář úspěšně zpracován");
                
                if (empty($this->errors)) {
                    $adminLink = $this->context->link->getAdminLink('AdminTechnologie');
                    $this->debugLog("Přesměrovávám na: $adminLink");
                    Tools::redirectAdmin($adminLink);
                }
            } else {
                $this->debugLog("Formulář zpracován s chybami");
            }
        }

        // Příprava dat pro šablonu
        $technologieData = $this->prepareTechnologieData($technologie);
        $this->debugLog("Data pro šablonu", $technologieData);

        $this->context->smarty->assign([
            'technologie' => $technologieData,
            'is_edit' => (bool)$this->id_object,
            'table' => $this->table, // Důležité pro správné jméno submit tlačítka
            'upload_dir' => _MODULE_DIR_ . 'technologie/uploads/',
            'back_url' => $this->context->link->getAdminLink('AdminTechnologie'),
            'errors' => $this->errors
        ]);

        $this->debugLog("=== KONEC renderForm ===");
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'technologie/views/templates/admin/form.tpl');
    }

    // Zbytek metod zůstává stejný...
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

    // Bulk action metody...
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