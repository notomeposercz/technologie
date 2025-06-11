# Instalační příručka - Modul Technologie potisku

## 🔧 Předpoklady

### Systémové požadavky
- PrestaShop 8.2.0 nebo novější
- PHP 8.1+ s rozšířeními:
  - GD nebo Imagick
  - fileinfo
  - iconv
  - PDO MySQL
- MySQL 5.7+ nebo MariaDB 10.2+
- Webserver (Apache/Nginx) s mod_rewrite

### Kontrola požadavků
```bash
# Kontrola verze PHP
php -v

# Kontrola rozšíření PHP
php -m | grep -E "(gd|fileinfo|iconv|pdo_mysql)"

# Kontrola oprávnění
ls -la modules/
```

## 📦 Instalace

### Metoda 1: Přes admin rozhraní (doporučeno)

1. **Příprava souborů**
   ```bash
   # Zkopírování modulu do správného adresáře
   cp -r technologie/ /path/to/prestashop/modules/
   
   # Nastavení oprávnění
   chmod -R 755 modules/technologie/
   chmod -R 644 modules/technologie/views/
   chmod 755 modules/technologie/uploads/
   ```

2. **Instalace v admin**
   - Přihlaste se do PrestaShop admin
   - Přejděte na `Moduly > Správce modulů`
   - Najděte "Technologie potisku"
   - Klikněte na "Instalovat"

### Metoda 2: Manuální instalace

1. **Příprava databáze**
   ```sql
   -- Spuštění SQL skriptu
   SOURCE modules/technologie/sql/install.sql;
   ```

2. **Registrace modulu**
   ```bash
   cd /path/to/prestashop
   php bin/console prestashop:module install technologie
   ```

### Metoda 3: Vývojářská instalace

1. **Klonování z Git**
   ```bash
   cd modules/
   git clone [repository-url] technologie
   cd technologie
   ```

2. **Instalace závislostí** (pokud jsou)
   ```bash
   composer install --no-dev
   ```

3. **Instalace modulu**
   ```bash
   php bin/console prestashop:module install technologie
   ```

## ⚙️ Konfigurace

### 1. Základní nastavení

Po instalaci:
1. Přejděte na `Vylepšení > Správa potisků`
2. Přidejte první technologii
3. Otestujte zobrazení na `/reklamni-potisk`

### 2. Nastavení oprávnění

```bash
# Oprávnění pro upload obrázků
chmod 755 modules/technologie/uploads/

# Oprávnění pro cache (pokud používáte)
chmod 755 var/cache/

# Oprávnění pro logy
chmod 755 var/logs/
```

### 3. Webserver konfigurace

#### Apache (.htaccess)
```apache
# Již součástí PrestaShop .htaccess
# Žádná další konfigurace není potřeba
```

#### Nginx
```nginx
# Přidání do nginx konfigurace
location /reklamni-potisk {
    try_files $uri $uri/ /index.php?$args;
}

# Statické soubory modulu
location ~* ^/modules/technologie/uploads/.*\.(jpg|jpeg|png|gif|webp)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## 🧪 Testování instalace

### 1. Základní funkčnost
```bash
# Test databázové tabulky
mysql -u [user] -p[password] [database] -e "DESCRIBE ps_technologie;"

# Test souborů
ls -la modules/technologie/
```

### 2. Admin rozhraní
1. Přihlaste se do admin
2. Přejděte na `Vylepšení > Správa potisků`
3. Přidejte testovací technologii
4. Ověřte upload obrázku

### 3. Front office
1. Přejděte na `/reklamni-potisk`
2. Ověřte zobrazení technologií
3. Zkontrolujte responzivní design

### 4. SEO test
```bash
# Test meta tagů
curl -s "http://your-domain.com/reklamni-potisk" | grep -E "<title>|<meta"

# Test structured data
curl -s "http://your-domain.com/reklamni-potisk" | grep "application/ld+json"
```

## 🔧 Řešení problémů

### Chyba při instalaci

**Problém**: "Modul se nepodařilo nainstalovat"
```bash
# Kontrola logů
tail -f var/logs/dev.log

# Kontrola oprávnění
ls -la modules/technologie/

# Ruční spuštění SQL
mysql -u [user] -p [database] < modules/technologie/sql/install.sql
```

### Chyba 404 na /reklamni-potisk

**Problém**: Stránka nenalezena
```bash
# Vyčištění cache
php bin/console cache:clear

# Kontrola routing
grep -r "reklamni-potisk" modules/technologie/

# Restart webserveru
sudo service apache2 restart
# nebo
sudo service nginx restart
```

### Problémy s obrázky

**Problém**: Obrázky se nenačítají
```bash
# Kontrola adresáře uploads
ls -la modules/technologie/uploads/
chmod 755 modules/technologie/uploads/

# Kontrola PHP nastavení
php -i | grep -E "(upload_max_filesize|post_max_size|max_file_uploads)"
```

## 🔄 Aktualizace

### Z verze 1.0.x na 1.1.x
```bash
# Zálohování
cp -r modules/technologie/ modules/technologie_backup/

# Aktualizace souborů
# ... zkopírování nových souborů ...

# Spuštění upgrade skriptu (pokud existuje)
php modules/technologie/upgrade/upgrade-1.1.0.php
```

## 🗑️ Odinstalace

### Kompletní odstranění
```bash
# Odinstalace přes admin nebo CLI
php bin/console prestashop:module uninstall technologie

# Ruční odstranění souborů
rm -rf modules/technologie/

# Ruční odstranění z databáze (pokud potřeba)
mysql -u [user] -p [database] -e "DROP TABLE IF EXISTS ps_technologie;"
```

### Zachování dat
```bash
# Pouze deaktivace modulu
php bin/console prestashop:module disable technologie
```

## 📞 Podpora

Pokud máte problémy s instalací:
1. Zkontrolujte systémové požadavky
2. Přečtěte si logy v `var/logs/`
3. Kontaktujte podporu s detailním popisem problému

**Email**: mike.u@centrum.cz  
**Verze**: 1.0.0  
**Datum**: 2024-12-19
