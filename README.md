# Modul Technologie potisku pro PrestaShop 8.2.0

Moderní modul pro správu a zobrazení technologií potisku na vlastní podstránce `/reklamni-potisk`.

## 📋 Přehled

Tento modul umožňuje:
- ✅ Správu technologií potisku v admin rozhraní
- ✅ Zobrazení technologií na front office stránce
- ✅ Upload a optimalizaci obrázků
- ✅ Drag & drop řazení
- ✅ Hromadné akce
- ✅ Responzivní design
- ✅ SEO optimalizaci

## 🔧 Požadavky

- **PrestaShop**: 8.2.0+
- **PHP**: 8.1+
- **MySQL**: 5.7+
- **Rozšíření PHP**: GD, fileinfo, iconv
- **Webserver**: Apache/Nginx s mod_rewrite

## 📦 Instalace

1. **Stažení modulu**
   ```bash
   # Zkopírování modulu do PrestaShop instalace
   cp -r technologie/ /path/to/prestashop/modules/
   ```

2. **Instalace přes admin**
   - Přejděte do `Moduly > Správce modulů`
   - Najděte modul "Technologie potisku"
   - Klikněte na "Instalovat"

3. **Manuální instalace**
   ```bash
   cd /path/to/prestashop
   php bin/console prestashop:module install technologie
   ```

## 🚀 Použití

### Admin rozhraní
- Přejděte do `Vylepšení > Správa potisků`
- Přidejte nové technologie pomocí formuláře
- Upravte pořadí pomocí drag & drop
- Použijte hromadné akce pro správu více položek

### Front office
- Technologie se zobrazují na URL `/reklamni-potisk`
- Pouze aktivní technologie jsou viditelné
- Automatické SEO optimalizace

## 📁 Struktura souborů

```
modules/technologie/
├── technologie.php              # Hlavní soubor modulu
├── config/
│   └── routes.yaml             # Routing konfigurace
├── controllers/
│   ├── admin/
│   │   └── AdminTechnologieController.php
│   └── front/
│       └── TechnologieController.php
├── src/
│   ├── Entity/
│   │   └── Technologie.php     # Doctrine entity
│   ├── Repository/
│   │   └── TechnologieRepository.php
│   ├── Form/
│   │   ├── TechnologieType.php
│   │   └── FileUploadHandler.php
│   ├── Security/
│   │   └── SecurityManager.php
│   └── Service/
│       ├── ImageOptimizer.php
│       └── CacheManager.php
├── views/
│   ├── templates/
│   │   ├── admin/
│   │   │   └── form.tpl
│   │   └── front/
│   │       ├── technologie.tpl
│   │       └── error.tpl
│   ├── css/
│   │   ├── admin.css
│   │   └── front.css
│   └── js/
│       ├── admin.js
│       └── front.js
├── sql/
│   ├── install.sql
│   └── uninstall.sql
├── uploads/                    # Adresář pro obrázky
├── translations/              # Překlady
├── README.md
├── INSTALL.md
└── CHANGELOG.md
```

## 🔒 Bezpečnost

- CSRF ochrana ve všech formulářích
- Validace nahrávaných souborů
- XSS prevence v šablonách
- SQL injection ochrana přes Doctrine ORM
- Sanitizace všech vstupních dat

## ⚡ Výkon

- Cache systém pro databázové dotazy
- Optimalizace obrázků při uploadu
- Lazy loading obrázků
- Minimalizace HTTP požadavků

## 🌐 SEO

- Správné meta tagy
- Open Graph tagy
- Structured data (JSON-LD)
- Breadcrumb navigace
- Kanonické URL

## 🐛 Řešení problémů

### Modul se neinstaluje
1. Zkontrolujte oprávnění souborů (755 pro adresáře, 644 pro soubory)
2. Ověřte PHP požadavky
3. Zkontrolujte logy v `var/logs/`

### Obrázky se nenačítají
1. Zkontrolujte oprávnění adresáře `uploads/` (755)
2. Ověřte nastavení webserveru pro statické soubory
3. Zkontrolujte velikost nahrávaných souborů

### Stránka /reklamni-potisk nefunguje
1. Vyčistěte cache (`php bin/console cache:clear`)
2. Zkontrolujte routing v `config/routes.yaml`
3. Ověřte mod_rewrite na webserveru

## 📞 Podpora

Pro technickou podporu kontaktujte:
- Email: mike.u@centrum.cz
- GitHub Issues: [repository-url]/issues

## 📄 Licence

Tento modul je licencován pod MIT licencí.

## 🔄 Changelog

Viz [CHANGELOG.md](CHANGELOG.md) pro historii změn.

## 👨‍💻 Autor

Vytvořeno pro PrestaShop 8.2.0 s použitím moderních PHP 8.1+ postupů.

**Verze**: 1.0.0  
**Datum vydání**: 2024-12-19  
**Kompatibilita**: PrestaShop 8.2.0+
