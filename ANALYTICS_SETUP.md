# Setup Sistema Analytics qrArt

## Panoramica

Il sistema analytics di qrArt traccia automaticamente:
- Scansioni QR code
- Visualizzazioni contenuti
- Eventi playback (audio/video: play, pause, complete, error)
- Distribuzione per dispositivo (mobile/tablet/desktop)
- Distribuzione per lingua
- Sessioni utente e journey

## Requisiti

- PHP 8.1+
- MySQL 5.7+ o MariaDB 10.3+
- CodeIgniter 4
- Composer

## Installazione

### 1. Verifica Database

Assicurati che il database MySQL sia in esecuzione e accessibile.

```bash
# Verifica che MySQL sia attivo
sudo systemctl status mysql
# oppure
sudo service mysql status
```

### 2. Configura Credenziali Database

Verifica le credenziali in `app/Config/Database.php`:

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

### 3. Installa Dipendenze

```bash
composer install
```

### 4. Crea Tabelle Analytics

#### Opzione A: Usando le Migrations (Raccomandato)

```bash
php spark migrate
```

Questo creerà automaticamente le tabelle:
- `analytics_events`
- `content_metrics`
- `user_sessions`

#### Opzione B: Script SQL Manuale

Se le migrations non funzionano, esegui manualmente lo script SQL:

```bash
mysql -u developer.qrart -p qrart < database_setup_analytics.sql
```

### 5. Verifica Setup

Visita l'endpoint di health check:

```bash
curl http://localhost/api/analytics/health
```

Risposta attesa:
```json
{
    "success": true,
    "message": "Analytics system is ready",
    "database": "qrart",
    "tables": ["analytics_events", "content_metrics", "user_sessions"]
}
```

## Utilizzo

### Dashboard Analytics

Accedi alla dashboard per visualizzare le statistiche:

```
http://localhost/analytics/dashboard
```

La dashboard mostra:
- Metriche principali (scansioni, visualizzazioni, visitatori unici)
- Grafici interattivi (dispositivi, lingue, timeline)
- Top 10 contenuti più visualizzati
- Eventi recenti

### API Endpoints

#### Health Check
```
GET /api/analytics/health
```

#### Tracciamento Eventi
```
POST /api/analytics/track
Content-Type: application/json

{
    "event_type": "playback_start",
    "content_id": 123,
    "language": "it"
}
```

#### Statistiche Globali
```
GET /api/analytics/stats/overview
```

#### Statistiche Filtrate
```
GET /api/analytics/stats?start_date=2025-01-01&end_date=2025-01-31
```

#### Metriche per Contenuto
```
GET /api/analytics/content/{id}
```

#### Metriche Aggregate
```
GET /api/analytics/metrics
```

#### Eventi Recenti
```
GET /api/analytics/content/{id}/events?limit=100
```

## Tracking Automatico

Il tracking è automatico per:

### Backend (PHP)
- **QR Scan**: Ogni accesso a `/api/content/{shortCode}` viene tracciato
- **HTML Content**: Ogni accesso a `/content/html/{id}/{lang}` viene tracciato

### Frontend (AngularJS)
- **Audio Player**: Eventi play, pause, ended, error
- **Video Player**: Eventi play, pause, ended, error
- Tutti gli eventi sono inviati automaticamente all'API

## Struttura Database

### Tabella: analytics_events
Memorizza tutti gli eventi individuali.

**Colonne principali:**
- `event_type`: Tipo evento (qr_scan, playback_start, etc.)
- `content_id`: ID contenuto associato
- `session_id`: ID sessione utente
- `device_type`: mobile/tablet/desktop
- `browser`, `os`: Informazioni browser e sistema operativo
- `metadata`: Dati JSON aggiuntivi

### Tabella: content_metrics
Metriche aggregate per contenuto (aggiornate in tempo reale).

**Colonne principali:**
- `total_scans`: Scansioni QR totali
- `unique_visitors`: Visitatori unici
- `playback_starts`: Riproduzioni avviate
- `playback_completes`: Riproduzioni completate
- `avg_completion_rate`: Tasso di completamento %
- `language_stats`: JSON con statistiche per lingua
- `device_stats`: JSON con statistiche per dispositivo

### Tabella: user_sessions
Traccia sessioni utente e journey.

**Colonne principali:**
- `session_id`: UUID univoco per sessione
- `first_seen`, `last_seen`: Timestamps attività
- `total_events`: Numero eventi nella sessione
- `contents_viewed`: JSON array di content_id visualizzati

## Troubleshooting

### Errore: "Database not connected"

**Problema**: Il database MySQL non è accessibile.

**Soluzione**:
1. Verifica che MySQL sia in esecuzione
2. Controlla le credenziali in `app/Config/Database.php`
3. Testa la connessione: `mysql -u developer.qrart -p -h localhost qrart`

### Errore: "Analytics tables not found"

**Problema**: Le tabelle analytics non esistono.

**Soluzione**:
1. Esegui `php spark migrate`
2. Oppure esegui lo script SQL manuale: `database_setup_analytics.sql`

### Dashboard mostra "Errore nel caricamento dei dati"

**Problema**: Le API non rispondono correttamente.

**Soluzione**:
1. Verifica health check: `curl http://localhost/api/analytics/health`
2. Controlla i log in `writable/logs/`
3. Verifica che le tabelle esistano nel database

### Nessun dato nel dashboard

**Problema**: Non ci sono ancora eventi tracciati.

**Soluzione**:
1. Scansiona un QR code o visualizza un contenuto
2. Verifica che ci siano record in `analytics_events`
3. Controlla che il tracking frontend sia attivo (console browser)

## Performance

Il sistema analytics è ottimizzato per:
- Inserimenti batch di eventi
- Metriche aggregate pre-calcolate
- Indici su colonne più interrogate
- Pulizia automatica eventi vecchi (da implementare se necessario)

## Sicurezza

- Gli endpoint analytics NON richiedono autenticazione (per permettere tracking anonimo)
- Se necessario, aggiungi autenticazione negli endpoint di lettura statistiche
- Gli IP sono memorizzati ma possono essere anonimizzati se richiesto dal GDPR

## Prossimi Passi

Per migliorare il sistema analytics:
1. Aggiungi autenticazione agli endpoint di lettura
2. Implementa pulizia automatica eventi vecchi
3. Aggiungi export CSV/Excel delle statistiche
4. Implementa real-time notifications (WebSocket)
5. Aggiungi heatmaps e funnel analysis
