<?php
/**
 * Admin controller pro správu technologií - KOMPLETNÍ DEBUG VERZE
 *
 * @author PrestaShop Module Generator
 * @copyright 2024
 * @license MIT
 */

// Načtení autoloaderu modulu
require_once _PS_MODULE_DIR_ . 'technologie/technologie.php';

use PrestaShop\Module\Technologie\Entity\Technologie as TechnologieEntity;
use PrestaShop\Module\Technologie\Repository\TechnologieRepository;
use PrestaShop\Module\Technologie\Repository\TechnologieDbRepository;
use PrestaShop\Module\Technologie\Form\TechnologieType;
use PrestaShop\Module\Technologie\Form\BulkActionType;
use PrestaShop\Module\Technologie\Form\FileUploadHandler;

/**
 * Admin controller pro správu technologií potisku
 */
class AdminTechnologieController extends ModuleAdminController
{
    private $technologieRepository = null;
    private $fileUploadHandler = null;

    /**
     * Konstruktor - inicializace controlleru
     */
    public function __construct($bootstrap = true, $display = true)
    {
        $this->bootstrap = true;
        $this->table = 'technologie';
        $this->className = 'TechnologieModel';
        $this->lang = false;
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        // Nejdříve zavolat parent konstruktor pro inicializaci translatoru
        parent::__construct($bootstrap, $display);

        // Konfigurace hromadných akcí (po inicializaci translatoru)
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
     * Debug funkce pro logování do souboru
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

    /**
     * Lazy inicializace TechnologieRepository
     */
    private function getTechnologieRepository()
    {
        if ($this->technologieRepository === null) {
            // Pokus o použití Doctrine
            try {
                if (method_exists($this, 'get') && $this->get('doctrine.orm.entity_manager')) {
                    $entityManager = $this->get('doctrine.orm.entity_manager');
                    $this->technologieRepository = $entityManager->getRepository(TechnologieEntity::class);
                } else {
                    throw new \Exception('Doctrine není dostupné');
                }
            } catch (\Exception $e) {
                // Fallback - použití DB repository
                $this->technologieRepository = new TechnologieDbRepository();
            }
        }
        return $this->technologieRepository;
    }

    /**
     * Lazy inicializace FileUploadHandler
     */
    private function getFileUploadHandler()
    {
        if ($this->fileUploadHandler === null) {
            $this->fileUploadHandler = new FileUploadHandler();
        }
        return $this->fileUploadHandler;
    }

    /**
     * Zajištění existence upload adresáře
     */
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

        // Vytvoření .htaccess pro bezpečnost
        $htaccessPath = $uploadDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "Options -Indexes\n";
            $htaccessContent .= "RedirectMatch 403 \.php$\n";
            file_put_contents($htaccessPath, $htaccessContent);
        }

        return $uploadDir;
    }

    /**
     * Validace nahraného souboru
     */
    private function validateUploadedFile(array $file): bool
    {
        $this->debugLog("=== validateUploadedFile START ===");
        $this->debugLog("Validace souboru", $file);
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // Kontrola velikosti
        if ($file['size'] > $maxSize) {
            $this->debugLog("CHYBA: Soubor příliš velký: " . $file['size'] . " > $maxSize");
            $this->errors[] = $this->l('Soubor je příliš velký. Maximální velikost je 2MB');
            return false;
        }

        // Kontrola MIME typu pomocí finfo
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

        // Kontrola pomocí getimagesize (ověří že je to skutečně obrázek)
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

    /**
     * Generování bezpečného názvu souboru
     */
    private function generateSecureFilename(array $file): string
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Fallback pro extension pokud není v názvu
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

    /**
     * Logování chyb uploadu
     */
    private function logUploadError(int $errorCode): void
    {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Překročena maximální velikost souboru (php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'Překročena maximální velikost souboru (formulář)',
            UPLOAD_ERR_PARTIAL => 'Soubor byl nahrán pouze částečně',
            UPLOAD_ERR_NO_FILE => 'Nebyl vybrán žádný soubor',
            UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasný adresář',
            UPLOAD_ERR_CANT_WRITE => 'Nelze zapsat soubor na disk',
            UPLOAD_ERR_EXTENSION => 'Upload zastaven rozšířením PHP'
        ];

        $message = $errorMessages[$errorCode] ?? 'Neznámá chyba uploadu';
        $this->errors[] = $this->l('Chyba uploadu: ') . $message;
        error_log("Technologie upload error code $errorCode: $message");
        $this->debugLog("Upload error: $message (kód: $errorCode)");
    }

    /**
     * Vylepšené zpracování upload obrázku s debug logováním
     */
    private function handleImageUpload(array $file)
    {
        $this->debugLog("=== handleImageUpload START ===");
        $this->debugLog("Input file data", $file);
        
        try {
            // Detailní kontrola chyb uploadu
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->debugLog("Upload error code: " . $file['error']);
                $this->logUploadError($file['error']);
                return null;
            }

            // Kontrola že tmp_name existuje a je čitelný
            if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
                $this->debugLog("CHYBA: tmp_name neexistuje nebo není čitelný: " . $file['tmp_name']);
                $this->errors[] = $this->l('Dočasný soubor není dostupný');
                return null;
            }

            $this->debugLog("tmp_name OK, velikost: " . filesize($file['tmp_name']) . " bytes");

            // Validace souboru
            if (!$this->validateUploadedFile($file)) {
                $this->debugLog("Validace souboru selhala");
                return null;
            }

            $this->debugLog("Validace souboru prošla");

            // Vytvoření upload adresáře
            $uploadDir = $this->ensureUploadDirectory();
            if (!$uploadDir) {
                $this->debugLog("CHYBA: Nepodařilo se zajistit upload adresář");
                return null;
            }

            $this->debugLog("Upload adresář OK: $uploadDir");

            // Generování bezpečného názvu souboru
            $filename = $this->generateSecureFilename($file);
            $targetPath = $uploadDir . $filename;

            $this->debugLog("Generovaný název: $filename");
            $this->debugLog("Cílová cesta: $targetPath");

            // Přesun souboru s kontrolou
            $this->debugLog("Pokus o move_uploaded_file z '" . $file['tmp_name'] . "' do '$targetPath'");
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->debugLog("move_uploaded_file ÚSPĚŠNÝ");
                
                chmod($targetPath, 0644);
                $this->debugLog("chmod 644 nastaven");
                
                // Ověření, že soubor existuje a je čitelný
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
                $this->debugLog("Možné příčiny: oprávnění, místo na disku, cesta");
                $this->errors[] = $this->l('Nepodařilo se přesunout nahraný soubor');
                return null;
            }

        } catch (\Exception $e) {
            $this->debugLog("=== VÝJIMKA V handleImageUpload ===");
            $this->debugLog("Chyba: " . $e->getMessage());
            $this->debugLog("Stack trace: " . $e->getTraceAsString());
            
            $this->errors[] = $this->l('Chyba při zpracování obrázku: ') . $e->getMessage();
            error_log('Technologie image upload error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Zpracování POST požadavku pro uložení technologie s debug logováním
     */
    private function processFormSubmission($technologie = null)
{
    $this->debugLog("=== ZAČÁTEK processFormSubmission ===");
    
    // DEBUG všech submit hodnot
    $this->debugLog("Všechny POST klíče", array_keys($_POST));
    $this->debugLog("Hledám submit tlačítka:");
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'submit') === 0) {
            $this->debugLog("Nalezen submit: $key = $value");
        }
    }
        
        try {
            // Debug všech POST dat
            $this->debugLog("POST data", $_POST);
            $this->debugLog("FILES data", $_FILES);
            
            // Validace základních dat
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

            // Vytvoření nebo úprava entity
            if ($this->id_object && $technologie && method_exists($technologie, 'setName')) {
                $editTechnologie = $technologie;
                $this->debugLog("EDITACE existující technologie ID: " . $this->id_object);
            } else {
                $editTechnologie = new TechnologieEntity();
                $this->debugLog("VYTVÁŘENÍ nové technologie");
            }

            // Nastavení základních dat
            $editTechnologie->setName($name);
            $editTechnologie->setDescription($description);
            $editTechnologie->setActive($active);

            // Nastavení pozice
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

            // KLÍČOVÁ ČÁST - zpracování obrázku
            $this->debugLog("=== ZPRACOVÁNÍ OBRÁZKU ===");
            
            $currentImage = '';
            if ($this->id_object && method_exists($editTechnologie, 'getImage')) {
                $currentImage = $editTechnologie->getImage() ?? '';
                $this->debugLog("Současný obrázek: " . ($currentImage ?: 'žádný'));
            }

            // Kontrola FILES pole
            $this->debugLog("Kontrola FILES image", isset($_FILES['image']) ? $_FILES['image'] : 'NENÍ NASTAVENO');

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $this->debugLog("UPLOAD: Zahajuji upload nového obrázku");
                
                // Nahrání nového obrázku
                $newFilename = $this->handleImageUpload($_FILES['image']);
                $this->debugLog("Výsledek handleImageUpload: " . ($newFilename ?: 'NULL/FALSE'));
                
                if ($newFilename) {
                    $this->debugLog("ÚSPĚCH: Nový obrázek nahrán: $newFilename");
                    
                    // Smazání starého obrázku pouze pokud upload proběhl úspěšně
                    if ($currentImage && $currentImage !== $newFilename) {
                        $this->debugLog("Mazání starého obrázku: $currentImage");
                        $this->deleteImageFile($currentImage);
                    }
                    
                    $editTechnologie->setImage($newFilename);
                    $this->debugLog("Nový obrázek nastaven do entity: $newFilename");
                } else {
                    $this->debugLog("CHYBA: Upload se nezdařil, zachovávám starý obrázek");
                    // Chyba při uploadu - zachováme starý obrázek
                    if ($currentImage) {
                        $editTechnologie->setImage($currentImage);
                    }
                    return false; // Zastav ukládání kvůli chybě
                }
            } else {
                $this->debugLog("Žádný nový obrázek, zachovávám současný: " . ($currentImage ?: 'žádný'));
                // Žádný nový obrázek - zachováme stávající
                if ($currentImage) {
                    $editTechnologie->setImage($currentImage);
                }
            }

            // Debug entity před uložením
            $this->debugLog("Entity před uložením", [
                'id' => method_exists($editTechnologie, 'getId') ? $editTechnologie->getId() : 'N/A',
                'name' => $editTechnologie->getName(),
                'image' => method_exists($editTechnologie, 'getImage') ? $editTechnologie->getImage() : 'N/A',
                'position' => $editTechnologie->getPosition(),
                'active' => $editTechnologie->isActive()
            ]);

            // Uložení do databáze
            $this->debugLog("=== UKLÁDÁNÍ DO DATABÁZE ===");
            $this->getTechnologieRepository()->save($editTechnologie);
            $this->debugLog("Úspěšně uloženo do databáze");
            
            // Debug entity po uložení
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
            $this->debugLog("Stack trace: " . $e->getTraceAsString());
            
            $this->errors[] = $this->l('Chyba při ukládání: ') . $e->getMessage();
            error_log('Technologie save error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bezpečné smazání obrázku
     */
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
        
        return true; // Soubor neexistuje, považujeme za úspěch
    }

    /**
     * Příprava dat technologie pro šablonu
     */
    private function prepareTechnologieData($technologie): array
    {
        if ($this->id_object && $technologie && method_exists($technologie, 'getName')) {
            // Editace - převedeme entitu na pole pro šablonu
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
            // Nová technologie
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
     * Zobrazení seznamu technologií
     */
    public function renderList()
    {
        // Přidání CSS a JS pro admin
        $this->addCSS(_MODULE_DIR_ . 'technologie/views/css/admin.css');
        $this->addJS(_MODULE_DIR_ . 'technologie/views/js/admin.js');

        // Konfigurace polí pro seznam
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

    /**
     * Získání dat pro seznam
     */
    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        try {
            // Načtení všech technologií z repository
            $technologie = $this->getTechnologieRepository()->findAllOrderedByPosition();

            // Převod na formát očekávaný PrestaShop admin listem
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
            // Fallback - načtení z databáze přímo
            return $this->getListFromDatabase($id_lang, $order_by, $order_way, $start, $limit);
        }
    }

    /**
     * Fallback metoda pro načtení dat přímo z databáze
     */
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

        // Převod na správný formát s id_technologie jako klíčem
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

    /**
     * Zobrazení formuláře s opraveným zpracováním
     */
    public function renderForm()
    {
        $this->debugLog("=== ZAČÁTEK renderForm ===");
        $this->debugLog("id_object: " . $this->id_object);
        $this->debugLog("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        $this->debugLog("submitAddtechnologie submit: " . (Tools::isSubmit('submitAddtechnologie') ? 'ANO' : 'NE'));
        
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

        // Zpracování POST požadavku
        if (Tools::isSubmit('submitAdd' . $this->table) || Tools::isSubmit('submitAddtechnologie')) {
            $this->debugLog("Zpracovávám POST formulář");
            if ($this->processFormSubmission($technologie)) {
                $this->confirmations[] = $this->l('Technologie byla úspěšně uložena');
                $this->debugLog("Formulář úspěšně zpracován");
                
                // Přesměrování pouze při úspěchu
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
            'upload_dir' => _MODULE_DIR_ . 'technologie/uploads/',
            'back_url' => $this->context->link->getAdminLink('AdminTechnologie'),
            'errors' => $this->errors
        ]);

        $this->debugLog("=== KONEC renderForm ===");
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'technologie/views/templates/admin/form.tpl');
    }

    /**
     * Zpracování hromadných akcí - smazání
     */
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
                    // Smazání obrázku
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

    /**
     * Hromadná aktivace
     */
    public function processBulkEnableSelection()
    {
        $this->processBulkStatus(true);
    }

    /**
     * Hromadná deaktivace
     */
    public function processBulkDisableSelection()
    {
        $this->processBulkStatus(false);
    }

    /**
     * Společná metoda pro změnu stavu
     */
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

    /**
     * Smazání jednotlivé technologie
     */
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

            // Smazání obrázku
            if ($technologie->getImage()) {
                $this->deleteImageFile($technologie->getImage());
            }

            $this->getTechnologieRepository()->delete($technologie);
            $this->confirmations[] = $this->l('Technologie byla úspěšně smazána');

        } catch (\Exception $e) {
            $this->errors[] = $this->l('Chyba při mazání: ') . $e->getMessage();
        }
    }

    /**
     * Callback pro zobrazení obrázku v seznamu
     */
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

    /**
     * AJAX endpoint pro drag & drop řazení
     */
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