# qrArt

Sistema per creare e gestire contenuti multimediali accessibili tramite QR code con analytics integrato.

## 🚀 Quick Start

### 1. Prerequisiti

- PHP 8.1+
- MySQL 5.7+ o MariaDB 10.3+
- Composer

### 2. Setup Automatico

Esegui lo script di setup che:
- Verifica l'installazione di MySQL
- Testa la connessione al database
- Crea database e utente se necessario
- Esegue le migrations

```bash
./setup-database.sh
```

### 3. Setup Manuale

Se preferisci il setup manuale:

#### a. Verifica configurazione database

Il file `.env` contiene già le credenziali del database. Verifica che siano corrette:

```bash
cat .env | grep database
```

Se necessario, modifica `.env` con le tue credenziali.

#### b. Installa dipendenze
```bash
composer install
```

#### c. Avvia MySQL
```bash
sudo systemctl start mysql
# oppure
sudo service mysql start
```

#### c. Crea database e utente
```bash
mysql -u root -p
```

```sql
CREATE DATABASE qrart;
CREATE USER 'developer.qrart'@'localhost' IDENTIFIED BY 'pOngE0oYSiVAtRZ';
GRANT ALL PRIVILEGES ON qrart.* TO 'developer.qrart'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### d. Esegui le migrations
```bash
php spark migrate
```

### 4. Verifica Setup

Testa la connessione al database:
```bash
php spark migrate:status
```

Accedi alla dashboard analytics:
```
http://localhost/analytics/dashboard
```

## 📊 Sistema Analytics

Il sistema analytics traccia automaticamente:

- ✅ Scansioni QR code
- ✅ Visualizzazioni contenuti
- ✅ Eventi playback (play, pause, complete, error)
- ✅ Distribuzione per dispositivo
- ✅ Distribuzione per lingua
- ✅ Sessioni utente

### Endpoint Analytics

```bash
# Health check
curl http://localhost/api/analytics/health

# Statistiche globali
curl http://localhost/api/analytics/stats/overview

# Statistiche per contenuto
curl http://localhost/api/analytics/content/1
```

Vedi documentazione completa: [ANALYTICS_SETUP.md](ANALYTICS_SETUP.md)

## 🔧 Troubleshooting

### Database non connesso

**Problema:**
```
Database not connected
```

**Soluzione:**
1. Verifica che MySQL sia in esecuzione:
   ```bash
   sudo systemctl status mysql
   ```

2. Esegui lo script di setup:
   ```bash
   ./setup-database.sh
   ```

3. Oppure vedi: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

### Errore creazione contenuto

**Problema:**
```
Errore nella creazione del nuovo content
```

**Soluzione:**
```bash
php spark migrate
```

Vedi: [TROUBLESHOOTING.md](TROUBLESHOOTING.md#errore-errore-nella-creazione-del-nuovo-content)

### Dipendenze mancanti

**Problema:**
```
Class "Google\Client" not found
```

**Soluzione:**
```bash
composer install
```

## 📁 Struttura Progetto

```
qrArt/
├── app/
│   ├── Controllers/         # Controller dell'applicazione
│   │   ├── AnalyticsController.php
│   │   ├── ContentController.php
│   │   └── QrArtController.php
│   ├── Models/             # Model per database
│   ├── Libraries/          # Librerie custom
│   │   └── AnalyticsEventService.php
│   ├── Database/
│   │   └── Migrations/     # Migration database
│   └── Views/              # View PHP
│       └── analytics_dashboard.php
├── public/
│   ├── app.js              # App AngularJS
│   ├── components/         # Componenti AngularJS
│   │   ├── audioPlayer/
│   │   ├── videoPlayer/
│   │   └── ...
│   └── services/
│       └── analyticsService.js
├── composer.json           # Dipendenze PHP
├── setup-database.sh       # Script setup automatico
├── ANALYTICS_SETUP.md      # Guida setup analytics
└── TROUBLESHOOTING.md      # Guida risoluzione problemi
```

## 🎯 Funzionalità Principali

### Creazione Contenuti
- Contenuti audio, video, video call, audio call
- Supporto multi-lingua
- Upload file multimediali
- Editor HTML integrato (TinyMCE)
- Generazione automatica QR code

### Analytics
- Dashboard interattiva con grafici
- Metriche real-time
- Export dati
- API RESTful per integrazione

### QR Code
- Generazione automatica
- Short URL personalizzabili
- Tracking scansioni

## 🔒 Sicurezza

- Validazione input lato server
- Protezione SQL injection (CodeIgniter Query Builder)
- Upload file con controllo tipo MIME
- Gestione sessioni sicure

## 📝 API Endpoints

### Contenuti
```
POST   /api/qrart/process              - Crea contenuto
GET    /api/content/{shortCode}        - Ottieni contenuto
GET    /api/content/getlist            - Lista contenuti
DELETE /api/content/delete/{id}        - Elimina contenuto
```

### Analytics
```
GET    /api/analytics/health           - Health check
POST   /api/analytics/track            - Traccia evento
GET    /api/analytics/stats/overview   - Statistiche globali
GET    /api/analytics/metrics          - Metriche aggregate
GET    /api/analytics/content/{id}     - Stats contenuto
```

Vedi API completa: [ANALYTICS_SETUP.md](ANALYTICS_SETUP.md#api-endpoints)

## 🐛 Debug

Abilita debug mode in `.env`:
```env
CI_ENVIRONMENT = development
```

Controlla i log:
```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

## 📚 Documentazione

- [ANALYTICS_SETUP.md](ANALYTICS_SETUP.md) - Setup e utilizzo analytics
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Risoluzione problemi comuni
- [database_setup_analytics.sql](database_setup_analytics.sql) - SQL setup manuale

## 💬 Supporto

Per problemi o domande:
1. Controlla [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Verifica i log in `writable/logs/`
3. Esegui lo script di diagnostica: `./setup-database.sh`

---

**Powered by CodeIgniter 4** | PHP 8.1+ | MySQL/MariaDB
