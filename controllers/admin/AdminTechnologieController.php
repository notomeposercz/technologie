<?php
/**
 * Admin controller pro správu technologií
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
     * Zpracování upload obrázku
     */
    private function handleImageUpload(array $file)
    {
        try {
            // Kontrola chyb uploadu
            if ($file['error'] !== UPLOAD_ERR_OK) {
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->errors[] = $this->l('Soubor je příliš velký');
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->errors[] = $this->l('Soubor byl nahrán pouze částečně');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $this->errors[] = $this->l('Nebyl vybrán žádný soubor');
                        break;
                    default:
                        $this->errors[] = $this->l('Chyba při nahrávání souboru');
                        break;
                }
                return null;
            }

            // Validace souboru
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            // Kontrola MIME typu
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes) && !in_array($file['type'], $allowedTypes)) {
                $this->errors[] = $this->l('Nepovolený typ souboru. Povolené formáty: JPG, PNG, GIF, WebP');
                return null;
            }

            if ($file['size'] > $maxSize) {
                $this->errors[] = $this->l('Soubor je příliš velký. Maximální velikost je 2MB');
                return null;
            }

            // Kontrola, že se jedná skutečně o obrázek
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $this->errors[] = $this->l('Soubor není platný obrázek');
                return null;
            }

            // Generování názvu souboru
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (empty($extension)) {
                // Pokud nemáme extension z názvu, použijeme z MIME typu
                $extensionMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp'
                ];
                $extension = $extensionMap[$mimeType] ?? 'jpg';
            }

            $filename = 'tech_' . time() . '_' . uniqid() . '.' . strtolower($extension);

            // Upload adresář
            $uploadDir = _PS_MODULE_DIR_ . 'technologie/uploads/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $this->errors[] = $this->l('Nelze vytvořit upload adresář');
                    return null;
                }
            }

            // Kontrola oprávnění k zápisu
            if (!is_writable($uploadDir)) {
                $this->errors[] = $this->l('Upload adresář není zapisovatelný');
                return null;
            }

            // Přesun souboru
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Nastavení správných oprávnění
                chmod($targetPath, 0644);
                return $filename;
            } else {
                $this->errors[] = $this->l('Chyba při nahrávání souboru do cílového adresáře');
                return null;
            }

        } catch (\Exception $e) {
            $this->errors[] = $this->l('Chyba při zpracování obrázku: ') . $e->getMessage();
            return null;
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
     * Zobrazení formuláře pro přidání/editaci
     */
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
        } else {
            // Pro novou technologii vytvoříme prázdný objekt pro šablonu
            $technologie = new stdClass();
            $technologie->getName = function() { return ''; };
            $technologie->getDescription = function() { return ''; };
            $technologie->getImage = function() { return ''; };
            $technologie->getPosition = function() {
                try {
                    return $this->getTechnologieRepository()->getMaxPosition() + 1;
                } catch (\Exception $e) {
                    return 1;
                }
            };
            $technologie->isActive = function() { return true; };
            $technologie->getImageUrl = function() { return ''; };
        }

        // Zpracování POST požadavku (bez Symfony formulářů)
        if (Tools::isSubmit('submitAddtechnologie')) {
            try {
                // Validace a nastavení dat
                $name = Tools::getValue('name');
                $description = Tools::getValue('description');
                if (empty($description)) {
                    $description = '';
                }
                $position = (int)Tools::getValue('position');
                $active = Tools::getValue('active') ? true : false;

                if (empty($name)) {
                    $this->errors[] = $this->l('Název technologie je povinný');
                } else {
                    // Pro editaci použijeme existující entitu, pro novou vytvoříme novou
                    if ($this->id_object && is_object($technologie) && method_exists($technologie, 'setName')) {
                        // Editace existující technologie
                        $editTechnologie = $technologie;
                    } else {
                        // Vytvoření nové technologie
                        $editTechnologie = new TechnologieEntity();
                    }

                    $editTechnologie->setName($name);
                    $editTechnologie->setDescription($description ?: '');

                    // Nastavení pozice - pokud není zadána nebo je 0, použijeme max + 1
                    if ($position <= 0) {
                        try {
                            $position = $this->getTechnologieRepository()->getMaxPosition() + 1;
                        } catch (\Exception $e) {
                            $position = 1;
                        }
                    }
                    $editTechnologie->setPosition($position);
                    $editTechnologie->setActive($active);

                    // Zpracování upload obrázku
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        // Smazání starého obrázku při editaci
                        if (method_exists($editTechnologie, 'getImage') && $editTechnologie->getImage()) {
                            $this->getFileUploadHandler()->deleteImage($editTechnologie->getImage());
                        }

                        $newFilename = $this->handleImageUpload($_FILES['image']);
                        if ($newFilename) {
                            $editTechnologie->setImage($newFilename);
                        }
                    } else {
                        // Pokud není nový obrázek a editujeme existující technologii, zachováme starý obrázek
                        if ($this->id_object && $technologie && method_exists($technologie, 'getImage')) {
                            $editTechnologie->setImage($technologie->getImage());
                        }
                    }

                    // Uložení do databáze
                    $this->getTechnologieRepository()->save($editTechnologie);

                    $this->confirmations[] = $this->l('Technologie byla úspěšně uložena');

                    // Přesměrování na seznam - pouze pokud nejsou chyby
                    if (empty($this->errors)) {
                        $adminLink = $this->context->link->getAdminLink('AdminTechnologie');
                        Tools::redirectAdmin($adminLink);
                    }
                }

            } catch (\Exception $e) {
                $this->errors[] = $this->l('Chyba při ukládání: ') . $e->getMessage();
            }
        }

        // Příprava dat pro šablonu
        $technologieData = null;
        if ($this->id_object && is_object($technologie) && method_exists($technologie, 'getName')) {
            // Editace - převedeme entitu na pole pro šablonu
            $imageUrl = '';
            if ($technologie->getImage()) {
                $imageUrl = _MODULE_DIR_ . 'technologie/uploads/' . $technologie->getImage();
            }

            $technologieData = [
                'name' => $technologie->getName(),
                'description' => $technologie->getDescription(),
                'image' => $technologie->getImage(),
                'position' => $technologie->getPosition(),
                'active' => $technologie->isActive() ? 1 : 0,
                'image_url' => $imageUrl
            ];
        } else {
            // Nová technologie - prázdné hodnoty
            $maxPosition = 1;
            try {
                $maxPosition = $this->getTechnologieRepository()->getMaxPosition() + 1;
            } catch (\Exception $e) {
                $maxPosition = 1;
            }

            $technologieData = [
                'name' => '',
                'description' => '',
                'image' => '',
                'position' => 0, // Necháme prázdné pro automatické přiřazení
                'active' => 1,
                'image_url' => ''
            ];
        }

        $this->context->smarty->assign([
            'technologie' => $technologieData,
            'is_edit' => (bool)$this->id_object,
            'upload_dir' => _MODULE_DIR_ . 'technologie/uploads/',
            'back_url' => $this->context->link->getAdminLink('AdminTechnologie'),
            'errors' => $this->errors
        ]);

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
                        $this->getFileUploadHandler()->deleteImage($technologie->getImage());
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
                $this->getFileUploadHandler()->deleteImage($technologie->getImage());
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
