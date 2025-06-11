<?php
/**
 * Modul Technologie potisku pro PrestaShop 8.2.0
 * 
 * @author Váš tým
 * @version 1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Autoloader pro namespace PrestaShop\Module\Technologie
spl_autoload_register(function ($class) {
    $prefix = 'PrestaShop\\Module\\Technologie\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * ObjectModel třída pro PrestaShop admin kompatibilitu
 */
class TechnologieModel extends ObjectModel
{
    /** @var int */
    public $id_technologie;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var string */
    public $image;

    /** @var int */
    public $position;

    /** @var bool */
    public $active;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'technologie',
        'primary' => 'id_technologie',
        'fields' => [
            'name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255
            ],
            'description' => [
                'type' => self::TYPE_HTML,
                'validate' => 'isCleanHtml',
                'required' => false
            ],
            'image' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => false,
                'size' => 255
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => false
            ],
            'active' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => false
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'copy_post' => false
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'copy_post' => false
            ],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
    }
}

/**
 * Hlavní třída modulu
 */
class Technologie extends Module
{
    public function __construct()
    {
        $this->name = 'technologie';
        $this->tab = 'administration';
        $this->version = '1.1.1';
        $this->author = 'Miroslav Urbánek';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Technologie potisku');
        $this->description = $this->l('Správa a zobrazení technologií potisku na vlastní podstránce');
        $this->confirmUninstall = $this->l('Opravdu chcete odinstalovat modul Technologie potisku?');
    }

    /**
     * Instalace modulu
     */
    public function install()
    {
        return parent::install() &&
            $this->installDb() &&
            $this->installTab() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('moduleRoutes');
    }

    /**
     * Odinstalace modulu
     */
    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallDb() &&
            $this->uninstallTab();
    }

    /**
     * Vytvoření databázové tabulky
     */
    private function installDb()
    {
        $sql = file_get_contents(__DIR__ . '/sql/install.sql');

        // Nahrazení PREFIX_ skutečným prefixem databáze
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        // Rozdělení na jednotlivé příkazy (kvůli INSERT statements)
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if (!Db::getInstance()->execute($statement)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Smazání databázové tabulky
     */
    private function uninstallDb()
    {
        $sql = file_get_contents(__DIR__ . '/sql/uninstall.sql');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);

        return Db::getInstance()->execute($sql);
    }

    /**
     * Vytvoření admin tabu
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminTechnologie';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Správa potisků';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('IMPROVE');
        $tab->module = $this->name;
        
        return $tab->add();
    }

    /**
     * Smazání admin tabu
     */
    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminTechnologie');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * Hook pro přidání CSS do hlavičky
     */
    public function hookDisplayHeader()
    {
        if ($this->context->controller instanceof TechnologieTechnologieModuleFrontController) {
            $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        }
    }

    /**
     * Hook pro registraci vlastních routes
     */
    public function hookModuleRoutes()
    {
        return [
            'module-technologie-list' => [
                'controller' => 'technologie',
                'rule' => 'reklamni-potisk',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'technologie',
                    'controller' => 'technologie'
                ]
            ],
            'module-technologie-detail' => [
                'controller' => 'technologie',
                'rule' => 'reklamni-potisk/{slug}',
                'keywords' => [
                    'slug' => ['regexp' => '[a-zA-Z0-9\-]+', 'param' => 'slug']
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => 'technologie',
                    'controller' => 'technologie',
                    'action' => 'detail'
                ]
            ]
        ];
    }

    /**
     * Konfigurace modulu (zatím prázdná)
     */
    public function getContent()
    {
        $output = '';
        $output .= $this->displayConfirmation($this->l('Modul je úspěšně nainstalován!'));
        $output .= '<p>' . $this->l('Pro správu technologií přejděte do menu "Správa potisků".') . '</p>';

        return $output;
    }
}
