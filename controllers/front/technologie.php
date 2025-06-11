<?php
/**
 * Front Office controller pro zobrazení technologií potisku
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2024 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */



// Načtení autoloaderu modulu
require_once _PS_MODULE_DIR_ . 'technologie/technologie.php';

use PrestaShop\Module\Technologie\Entity\Technologie as TechnologieEntity;
use PrestaShop\Module\Technologie\Repository\TechnologieRepository;
use PrestaShop\Module\Technologie\Repository\TechnologieDbRepository;

class TechnologieTechnologieModuleFrontController extends ModuleFrontController
{
    private $technologieRepository = null;

    /**
     * Konstruktor controlleru
     */
    public function __construct()
    {
        parent::__construct();
        // Repository se inicializuje lazy v getTechnologie() metodě
    }

    /**
     * Inicializace controlleru - nastavení základních parametrů
     */
    public function init()
    {
        parent::init();
        
        // Nastavení meta informací pro SEO
        $this->context->smarty->assign([
            'meta_title' => $this->getMetaTitle(),
            'meta_description' => $this->getMetaDescription(),
            'meta_keywords' => $this->getMetaKeywords(),
            'canonical_url' => $this->getCanonicalURL()
        ]);
    }

    /**
     * Hlavní akce pro zobrazení seznamu technologií
     */
    public function initContent()
    {
        parent::initContent();

        try {
            // Načtení aktivních technologií
            $technologie = $this->getTechnologie();

            // Příprava dat pro šablonu
            $this->context->smarty->assign([
                'technologie' => $technologie,
                'page_title' => 'Technologie potisku',
                'page_description' => 'Přehled všech dostupných technologií potisku a jejich vlastností',
                'breadcrumb_title' => 'Technologie potisku',
                'module_dir' => _MODULE_DIR_ . 'technologie/',
                'upload_dir' => _MODULE_DIR_ . 'technologie/uploads/',
                'has_technologie' => !empty($technologie)
            ]);

            // Nastavení breadcrumb navigace
            $this->addBreadcrumb();

            // Přidání CSS a JS assets
            $this->addAssets();

            // Nastavení šablony pro zobrazení
            $this->setTemplate('module:technologie/views/templates/front/technologie.tpl');

        } catch (\Exception $e) {
            // Logování chyby do PrestaShop logu
            PrestaShopLogger::addLog(
                'Technologie module error: ' . $e->getMessage(),
                3,
                null,
                'Technologie',
                null,
                true
            );

            // Zobrazení chybové stránky uživateli
            $this->context->smarty->assign([
                'error_message' => 'Omlouváme se, došlo k chybě při načítání technologií.'
            ]);
            
            $this->setTemplate('module:technologie/views/templates/front/error.tpl');
        }
    }

    /**
     * Načtení technologií - s fallback na databázové dotazy
     */
    private function getTechnologie()
    {
        // Lazy inicializace repository
        if ($this->technologieRepository === null) {
            try {
                // Pokus o inicializaci Doctrine repository
                if (method_exists($this, 'get')) {
                    $entityManager = $this->get('doctrine.orm.entity_manager');
                    if ($entityManager) {
                        $this->technologieRepository = $entityManager->getRepository(TechnologieEntity::class);
                    }
                }
            } catch (\Exception $e) {
                // Doctrine není dostupné, použijeme fallback
                PrestaShopLogger::addLog(
                    'Technologie module: Failed to initialize repository: ' . $e->getMessage(),
                    2,
                    null,
                    'Technologie'
                );
                // Nastavíme fallback repository
                $this->technologieRepository = new TechnologieDbRepository();
            }

            // Pokud se nepodařilo inicializovat Doctrine, použijeme fallback
            if ($this->technologieRepository === null) {
                $this->technologieRepository = new TechnologieDbRepository();
            }
        }

        if ($this->technologieRepository) {
            try {
                return $this->technologieRepository->findActiveOrderedByPosition();
            } catch (\Exception $e) {
                PrestaShopLogger::addLog(
                    'Technologie repository error: ' . $e->getMessage(),
                    2,
                    null,
                    'Technologie'
                );
            }
        }

        // Fallback - přímé databázové dotazy
        return $this->getTechnologieFromDatabase();
    }

    /**
     * Fallback metoda pro načtení technologií z databáze
     */
    private function getTechnologieFromDatabase()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'technologie` 
                WHERE active = 1 
                ORDER BY position ASC, name ASC';

        $results = Db::getInstance()->executeS($sql);
        
        if (!$results) {
            return [];
        }

        // Převod na objekty pro kompatibilitu se šablonou
        $technologie = [];
        foreach ($results as $row) {
            $tech = new stdClass();
            $tech->id = (int)$row['id_technologie'];
            $tech->name = $row['name'];
            $tech->description = $row['description'];
            $tech->image = $row['image'];
            $tech->position = (int)$row['position'];
            $tech->active = (bool)$row['active'];
            
            // Metody pro kompatibilitu
            $tech->getId = function() use ($tech) { return $tech->id; };
            $tech->getName = function() use ($tech) { return $tech->name; };
            $tech->getDescription = function() use ($tech) { return $tech->description; };
            $tech->getImage = function() use ($tech) { return $tech->image; };
            $tech->getPosition = function() use ($tech) { return $tech->position; };
            $tech->isActive = function() use ($tech) { return $tech->active; };
            $tech->getImageUrl = function() use ($tech) {
                return $tech->image ? _MODULE_DIR_ . 'technologie/uploads/' . $tech->image : null;
            };
            
            $technologie[] = $tech;
        }

        return $technologie;
    }

    /**
     * Akce pro detail technologie (připraveno pro budoucí rozšíření)
     */
    public function detailAction()
    {
        $slug = Tools::getValue('slug');
        
        if (!$slug) {
            Tools::redirect($this->context->link->getModuleLink('technologie', 'technologie'));
            return;
        }

        // Zatím přesměrování na hlavní stránku
        // V budoucnu zde bude implementován detail konkrétní technologie
        Tools::redirect($this->context->link->getModuleLink('technologie', 'technologie'));
    }

    /**
     * Přidání breadcrumb navigace
     */
    private function addBreadcrumb()
    {
        // Základní breadcrumb struktura
        $breadcrumb = [
            'links' => [
                [
                    'title' => 'Domů',
                    'url' => $this->context->link->getPageLink('index')
                ],
                [
                    'title' => 'Technologie potisku',
                    'url' => $this->context->link->getModuleLink('technologie', 'technologie')
                ]
            ]
        ];

        $this->context->smarty->assign('breadcrumb', $breadcrumb);
    }

    /**
     * Přidání CSS a JS souborů
     */
    private function addAssets()
    {
        // FontAwesome pro ikonky
        $this->addCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

        // CSS pro front office
        if (file_exists(_PS_MODULE_DIR_ . 'technologie/views/css/front.css')) {
            $this->addCSS(_MODULE_DIR_ . 'technologie/views/css/front.css');
        }

        // JavaScript pro interaktivitu (pokud bude potřeba)
        if (file_exists(_PS_MODULE_DIR_ . 'technologie/views/js/front.js')) {
            $this->addJS(_MODULE_DIR_ . 'technologie/views/js/front.js');
        }

        // Bootstrap pokud není načten v tématu
        if (!$this->isBootstrapLoaded()) {
            $this->addCSS('https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
        }
    }

    /**
     * Kontrola zda je Bootstrap načten v tématu
     */
    private function isBootstrapLoaded()
    {
        // Jednoduchá kontrola - předpokládáme, že Bootstrap není načten
        // V reálné implementaci by bylo lepší zkontrolovat skutečně načtené CSS soubory
        return false;
    }

    /**
     * Získání meta title pro SEO
     */
    private function getMetaTitle()
    {
        return 'Technologie potisku - ' . Configuration::get('PS_SHOP_NAME');
    }

    /**
     * Získání meta description pro SEO
     */
    private function getMetaDescription()
    {
        return 'Přehled všech dostupných technologií potisku. Sítotisk, digitální potisk, vyšívání, termotransfer a další moderní techniky pro potisk textilu a reklamních předmětů.';
    }

    /**
     * Získání meta keywords pro SEO
     */
    private function getMetaKeywords()
    {
        return 'technologie potisku, sítotisk, digitální potisk, vyšívání, termotransfer, potisk textilu, reklamní předměty';
    }

    /**
     * Získání kanonické URL
     */
    public function getCanonicalURL()
    {
        return $this->context->link->getModuleLink('technologie', 'technologie');
    }

    /**
     * Nastavení HTTP hlaviček a Open Graph meta tagů
     */
    public function setMedia()
    {
        parent::setMedia();
        
        // Přidání Open Graph meta tagů pro sociální sítě
        $this->context->smarty->assign([
            'og_title' => $this->getMetaTitle(),
            'og_description' => $this->getMetaDescription(),
            'og_url' => $this->getCanonicalURL(),
            'og_type' => 'website',
            'og_image' => 'http' . (Tools::usingSecureMode() ? 's' : '') . '://' . Tools::getHttpHost(false) . _MODULE_DIR_ . 'technologie/views/img/og-image.jpg'
        ]);
    }
}
