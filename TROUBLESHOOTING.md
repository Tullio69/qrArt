# Troubleshooting qrArt

## Errore: "Errore nella creazione del nuovo content"

### Sintomo
Quando si tenta di creare un nuovo contenuto, viene restituito il messaggio:
```json
{
  "success": false,
  "message": "Si è verificato un errore durante l'elaborazione: Errore nella creazione del nuovo content"
}
```

### Causa
Questo errore si verifica quando la tabella `content` nel database non ha la struttura corretta. Mancano i campi:
- `caller_name`
- `caller_title`
- `content_name`

### Soluzione

#### Opzione 1: Eseguire le Migrations (Raccomandato)

```bash
php spark migrate
```

Questo eseguirà la migration `UpdateContentTableStructure` che aggiungerà automaticamente i campi mancanti.

#### Opzione 2: Aggiornamento Manuale del Database

Se le migrations non funzionano, esegui questo SQL:

```sql
-- Aggiungi i campi mancanti alla tabella content
ALTER TABLE `content`
ADD COLUMN `caller_name` VARCHAR(255) NULL AFTER `caller_id`,
ADD COLUMN `caller_title` VARCHAR(255) NULL AFTER `caller_name`,
ADD COLUMN `content_name` VARCHAR(255) NULL AFTER `caller_title`;

-- Se esiste il campo 'title', rinominalo in 'content_name'
-- (Esegui solo se hai già il campo 'title')
-- ALTER TABLE `content` CHANGE `title` `content_name` VARCHAR(255) NULL;
```

#### Opzione 3: Verifica dello Schema Attuale

Prima di procedere, verifica quali campi esistono già:

```sql
DESCRIBE content;
```

Output atteso dopo la migrazione:
```
+---------------+--------------+------+-----+---------+----------------+
| Field         | Type         | Null | Key | Default | Extra          |
+---------------+--------------+------+-----+---------+----------------+
| id            | int unsigned | NO   | PRI | NULL    | auto_increment |
| caller_id     | int unsigned | YES  | MUL | NULL    |                |
| caller_name   | varchar(255) | YES  |     | NULL    |                |
| caller_title  | varchar(255) | YES  |     | NULL    |                |
| content_name  | varchar(255) | YES  |     | NULL    |                |
| content_type  | varchar(50)  | NO   |     | NULL    |                |
| description   | text         | YES  |     | NULL    |                |
| created_at    | timestamp    | YES  |     | NULL    |                |
| updated_at    | timestamp    | YES  |     | NULL    |                |
+---------------+--------------+------+-----+---------+----------------+
```

### Verifica della Risoluzione

Dopo aver applicato la soluzione:

1. Controlla i log per dettagli dell'errore:
   ```bash
   tail -f writable/logs/log-*.log
   ```

2. I log ora mostrano informazioni dettagliate:
   ```
   DEBUG - Tentativo di inserimento content: {"caller_name":"Test","caller_title":"Manager","content_name":"Test Content","content_type":"audio"}
   DEBUG - Content creato con ID: 123
   ```

3. Se vedi ancora errori, controlla il messaggio specifico nei log:
   ```
   ERROR - Errore inserimento content: {"field_name": "error message"}
   ```

## Errore: "Class DatabaseException not found"

### Causa
Manca l'import della classe `DatabaseException` nel controller.

### Soluzione
Questo errore è già stato risolto nel commit `e95081a`. Se lo vedi ancora, assicurati di aver fatto pull delle ultime modifiche:

```bash
git pull origin claude/implement-ana-feature-01JPkYotF14Qers9CHxLyAM9
```

## Errore Analytics Dashboard: "Errore nel caricamento dei dati"

### Causa
Il database non è connesso o le tabelle analytics non esistono.

### Soluzione
Vedi la documentazione completa in `ANALYTICS_SETUP.md`.

## Connessione Database Fallita

### Sintomo
```
Unable to connect to the database.
Main connection [MySQLi]: No such file or directory
```

### Cause Possibili

1. **MySQL non è in esecuzione**
   ```bash
   # Verifica stato
   sudo systemctl status mysql
   # oppure
   sudo service mysql status

   # Avvia se necessario
   sudo systemctl start mysql
   ```

2. **Credenziali errate**

   Verifica `app/Config/Database.php`:
   ```php
   public array $default = [
       'hostname' => 'localhost',
       'database' => 'qrart',
       'username' => 'developer.qrart',
       'password' => 'pOngE0oYSiVAtRZ',
       'DBDriver' => 'MySQLi',
       'port'     => 3306
   ];
   ```

3. **Socket MySQL errato**

   Se usi XAMPP/MAMP o configurazioni custom, potrebbe essere necessario specificare il socket:
   ```php
   public array $default = [
       // ... altri parametri ...
       'hostname' => 'localhost:/var/run/mysqld/mysqld.sock',
   ];
   ```

4. **Database non esiste**
   ```bash
   mysql -u root -p
   CREATE DATABASE qrart;
   GRANT ALL PRIVILEGES ON qrart.* TO 'developer.qrart'@'localhost' IDENTIFIED BY 'pOngE0oYSiVAtRZ';
   FLUSH PRIVILEGES;
   ```

## Permessi File/Directory

### Sintomo
Errori durante upload o creazione file.

### Soluzione
Assicurati che le directory siano scrivibili:

```bash
chmod -R 775 writable/
chmod -R 775 public/media/
chown -R www-data:www-data writable/
chown -R www-data:www-data public/media/
```

## Composer: Dipendenze Mancanti

### Sintomo
```
Class "Google\Client" not found
```

### Soluzione
```bash
composer install
```

Se composer.lock non esiste:
```bash
composer update
```

## Debug Generale

### Abilita Debug Mode

In `app/Config/Boot/development.php` o `.env`:
```env
CI_ENVIRONMENT = development
```

Questo mostrerà errori dettagliati nel browser.

### Controlla i Log

I log di CodeIgniter sono in:
```
writable/logs/log-YYYY-MM-DD.log
```

Visualizza in tempo reale:
```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

### Aumenta il Livello di Logging

In `app/Config/Logger.php`:
```php
public int $threshold = 9; // Log tutto (0-9)
```

## Supporto

Per problemi non coperti qui:

1. Controlla i log in `writable/logs/`
2. Verifica la configurazione del database
3. Assicurati che tutte le migrations siano eseguite: `php spark migrate:status`
4. Verifica che Composer abbia installato tutte le dipendenze: `composer install`
