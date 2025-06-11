# Testovací checklist - Modul Technologie potisku

## ✅ Pre-instalační testy

- [ ] PHP 8.1+ je nainstalováno
- [ ] Všechna požadovaná PHP rozšíření jsou aktivní (GD, fileinfo, iconv, PDO MySQL)
- [ ] PrestaShop 8.2.0+ je funkční
- [ ] Databáze je dostupná a funkční
- [ ] Oprávnění souborů jsou správně nastavena (755/644)

## ✅ Instalační testy

- [ ] Modul se úspěšně nainstaluje přes admin rozhraní
- [ ] Databázová tabulka `ps_technologie` je vytvořena s správnou strukturou
- [ ] Admin tab "Správa potisků" je přidán do menu
- [ ] Ukázková data jsou správně vložena (5 technologií)
- [ ] Adresář `uploads/` je vytvořen s správnými oprávněními (755)
- [ ] Routing pro `/reklamni-potisk` je registrován

## ✅ Admin rozhraní

### Základní funkce
- [ ] Seznam technologií se zobrazuje správně s paginací
- [ ] Formulář pro přidání nové technologie funguje bez chyb
- [ ] Formulář pro editaci existující technologie funguje
- [ ] Mazání technologie funguje s potvrzovacím dialogem
- [ ] Validační chyby se zobrazují správně

### Upload obrázků
- [ ] Upload JPG obrázku funguje (test s různými velikostmi)
- [ ] Upload PNG obrázku funguje (včetně průhlednosti)
- [ ] Upload GIF obrázku funguje (včetně animací)
- [ ] Upload WebP obrázku funguje
- [ ] Odmítnutí nepovolených formátů (txt, exe, php)
- [ ] Odmítnutí příliš velkých souborů (>2MB)
- [ ] Automatická optimalizace obrázků při uploadu
- [ ] Vytvoření WebP verzí pro moderní prohlížeče

### Hromadné akce
- [ ] Výběr všech technologií funguje
- [ ] Výběr jednotlivých technologií funguje
- [ ] Hromadná aktivace vybraných položek
- [ ] Hromadná deaktivace vybraných položek
- [ ] Hromadné mazání s potvrzovacím dialogem
- [ ] Správné zobrazení počtu zpracovaných položek

### Drag & Drop řazení
- [ ] Přetahování řádků funguje plynule
- [ ] Pozice se aktualizují v databázi okamžitě
- [ ] AJAX komunikace funguje bez chyb
- [ ] Loading indikátor se zobrazuje během aktualizace
- [ ] Error handling při selhání AJAX požadavku

### Bezpečnost admin
- [ ] CSRF tokeny jsou přítomny ve všech formulářích
- [ ] Neplatné CSRF tokeny jsou odmítnuty
- [ ] XSS pokusy jsou blokovány
- [ ] File upload bezpečnost (pouze obrázky)

## ✅ Front Office

### Základní zobrazení
- [ ] Stránka `/reklamni-potisk` je dostupná bez chyb
- [ ] Pouze aktivní technologie se zobrazují
- [ ] Technologie jsou seřazeny podle pozice (ASC)
- [ ] Obrázky se načítají správně s lazy loading
- [ ] Prázdný stav se zobrazuje když nejsou technologie

### Responzivní design
- [ ] Desktop zobrazení (1920px+) - grid layout
- [ ] Laptop zobrazení (1024px-1919px)
- [ ] Tablet zobrazení (768px-1023px)
- [ ] Mobilní zobrazení (320px-767px)
- [ ] Hover efekty fungují na desktop zařízeních
- [ ] Touch interakce fungují na mobilních zařízeních

### SEO optimalizace
- [ ] Meta title je správně nastaven
- [ ] Meta description je správně nastavena
- [ ] Meta keywords jsou relevantní
- [ ] Open Graph tagy jsou přítomny a správné
- [ ] Structured data (JSON-LD) jsou validní
- [ ] Breadcrumb navigace funguje správně
- [ ] Kanonická URL je správná
- [ ] H1-H6 tagy jsou správně strukturované

### Performance
- [ ] Stránka se načte pod 3 sekundy
- [ ] Obrázky mají lazy loading
- [ ] CSS a JS jsou minimalizované
- [ ] Cache headers jsou správně nastavené

## ✅ Bezpečnostní testy

### CSRF ochrana
- [ ] Admin formuláře mají CSRF tokeny
- [ ] Neplatné CSRF tokeny jsou odmítnuty s chybou
- [ ] AJAX požadavky jsou chráněny CSRF tokeny
- [ ] Formuláře bez tokenů jsou odmítnuty

### File upload bezpečnost
- [ ] Pouze povolené formáty obrázků jsou akceptovány
- [ ] Škodlivé soubory (php, exe) jsou odmítnuty
- [ ] Názvy souborů jsou správně sanitizovány
- [ ] Velikost souborů je kontrolována (max 2MB)
- [ ] MIME type je validován pomocí getimagesize()

### XSS ochrana
- [ ] HTML ve vstupech je správně escapován
- [ ] JavaScript injection je blokován
- [ ] Šablony používají auto-escaping
- [ ] Uživatelský obsah je sanitizován

### SQL Injection ochrana
- [ ] Všechny dotazy používají Doctrine ORM
- [ ] Parametrizované dotazy pro všechny vstupy
- [ ] Žádné raw SQL dotazy s uživatelskými daty

## ✅ Výkonnostní testy

### Cache systém
- [ ] Databázové dotazy jsou cachovány (TTL 1 hodina)
- [ ] Cache se invaliduje při změnách dat
- [ ] Cache klíče jsou správně nastaveny
- [ ] Bulk operace pro vyčištění cache fungují

### Optimalizace obrázků
- [ ] Velké obrázky jsou automaticky zmenšeny (max 800x600px)
- [ ] Kvalita obrázků je optimalizována (JPEG 85%, WebP 80%)
- [ ] WebP verze jsou vytvořeny pro podporované prohlížeče
- [ ] Poměr stran je zachován při změně velikosti

### Loading times
- [ ] Admin stránka se načte pod 2 sekundy
- [ ] Front office stránka se načte pod 3 sekundy
- [ ] AJAX operace jsou rychlé (pod 1 sekunda)
- [ ] Obrázky mají optimalizované loading

## ✅ Kompatibilita

### Prohlížeče Desktop
- [ ] Chrome (nejnovější verze)
- [ ] Firefox (nejnovější verze)
- [ ] Safari (nejnovější verze)
- [ ] Edge (nejnovější verze)
- [ ] Internet Explorer 11 (základní funkčnost)

### Mobilní prohlížeče
- [ ] iOS Safari (iPhone/iPad)
- [ ] Android Chrome
- [ ] Samsung Internet
- [ ] Responzivní design funguje na všech zařízeních

### PrestaShop kompatibilita
- [ ] PrestaShop 8.2.0 (minimální verze)
- [ ] PrestaShop 8.2.x (nejnovější patch verze)
- [ ] Kompatibilita s různými tématy
- [ ] Multistore podpora (základní)

## ✅ Error handling

### Chybové stavy
- [ ] Chyba databáze je správně zpracována a zalogována
- [ ] Chyba při uploadu je zobrazena uživateli
- [ ] 404 chyby jsou správně zpracovány
- [ ] Server chyby (500) jsou logovány bez odhalení citlivých dat

### Logování
- [ ] Chyby jsou logovány do PrestaShop logů
- [ ] Debug informace jsou dostupné ve vývojovém módu
- [ ] Produkční mód skrývá citlivé informace
- [ ] Log rotace funguje správně

## ✅ Finální kontrola

### Dokumentace
- [ ] README.md je kompletní a aktuální
- [ ] INSTALL.md obsahuje všechny potřebné kroky
- [ ] CHANGELOG.md je aktuální
- [ ] Komentáře v kódu jsou v češtině
- [ ] Kód samotný je v angličtině

### Soubory a oprávnění
- [ ] Všechny soubory jsou na svém místě
- [ ] Oprávnění souborů jsou správná (755/644)
- [ ] Žádné dočasné nebo testovací soubory
- [ ] Verze čísla jsou konzistentní napříč soubory

### Produkční připravenost
- [ ] Modul je připraven k distribuci
- [ ] Žádné debug výpisy v produkčním kódu
- [ ] Všechny TODO komentáře jsou vyřešeny
- [ ] Performance je optimalizovaná

## 🐛 Nalezené chyby

| Datum | Popis chyby | Priorita | Status | Poznámky |
|-------|-------------|----------|--------|----------|
|       |             |          |        |          |

## 📝 Poznámky k testování

- Testování provádět na čisté instalaci PrestaShop 8.2.0
- Používat různé velikosti a formáty obrázků pro upload testy
- Testovat s různými uživatelskými oprávněními
- Kontrolovat logy po každém testu
- Testovat na různých zařízeních a rozlišeních
- Ověřit funkcionalitu v různých prohlížečích

## ✅ Schválení k vydání

- [ ] Všechny testy prošly úspěšně
- [ ] Dokumentace je kompletní
- [ ] Bezpečnostní kontrola provedena
- [ ] Performance optimalizace dokončena
- [ ] Kompatibilita ověřena

**Datum testování**: ___________  
**Tester**: ___________  
**Verze**: 1.0.0  
**Status**: ⏳ ČEKÁ NA TESTOVÁNÍ
