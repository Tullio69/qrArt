#!/bin/bash

# Script di setup e diagnostica database per qrArt
# Usa questo script per verificare e configurare il database

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   qrArt - Database Setup & Check${NC}"
echo -e "${BLUE}========================================${NC}\n"

# 0. Verifica file .env
echo -e "${YELLOW}[0/6] Verifica file .env...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠ File .env non trovato${NC}"
    echo "Creazione file .env con configurazione predefinita..."
    cat > .env << 'ENVEOF'
CI_ENVIRONMENT = production

database.default.hostname = localhost
database.default.database = qrart
database.default.username = developer.qrart
database.default.password = pOngE0oYSiVAtRZ
database.default.DBDriver = MySQLi
database.default.port = 3306
ENVEOF
    echo -e "${GREEN}✓ File .env creato${NC}"
    echo -e "${YELLOW}  Modifica .env con le tue credenziali se necessario${NC}"
else
    echo -e "${GREEN}✓ File .env trovato${NC}"
fi

# 1. Verifica se MySQL è installato
echo -e "${YELLOW}[1/6] Verifica installazione MySQL...${NC}"
if command -v mysql &> /dev/null; then
    echo -e "${GREEN}✓ MySQL client trovato${NC}"
    mysql --version
elif command -v mariadb &> /dev/null; then
    echo -e "${GREEN}✓ MariaDB client trovato${NC}"
    mariadb --version
else
    echo -e "${RED}✗ MySQL/MariaDB client non trovato${NC}"
    echo -e "${YELLOW}Installa MySQL con:${NC}"
    echo "  sudo apt-get update"
    echo "  sudo apt-get install mysql-server mysql-client"
    echo ""
    echo -e "${YELLOW}Oppure MariaDB con:${NC}"
    echo "  sudo apt-get update"
    echo "  sudo apt-get install mariadb-server mariadb-client"
    exit 1
fi

# 2. Verifica se MySQL è in esecuzione
echo -e "\n${YELLOW}[2/6] Verifica servizio MySQL...${NC}"
if systemctl is-active --quiet mysql || systemctl is-active --quiet mariadb; then
    echo -e "${GREEN}✓ MySQL/MariaDB è in esecuzione${NC}"
elif service mysql status &> /dev/null || service mariadb status &> /dev/null; then
    echo -e "${GREEN}✓ MySQL/MariaDB è in esecuzione${NC}"
else
    echo -e "${RED}✗ MySQL/MariaDB non è in esecuzione${NC}"
    echo -e "${YELLOW}Prova ad avviarlo con:${NC}"
    echo "  sudo systemctl start mysql"
    echo "  # oppure"
    echo "  sudo service mysql start"
    echo ""
    read -p "Vuoi che provi ad avviarlo ora? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo systemctl start mysql || sudo service mysql start
        echo -e "${GREEN}✓ MySQL avviato${NC}"
    else
        echo -e "${YELLOW}Avvia MySQL manualmente e riesegui questo script${NC}"
        exit 1
    fi
fi

# 3. Verifica porta MySQL
echo -e "\n${YELLOW}[3/6] Verifica porta MySQL (3306)...${NC}"
if nc -zv localhost 3306 2>&1 | grep -q succeeded || nc -zv localhost 3306 2>&1 | grep -q open; then
    echo -e "${GREEN}✓ MySQL è in ascolto sulla porta 3306${NC}"
else
    echo -e "${RED}✗ MySQL non risponde sulla porta 3306${NC}"
    echo -e "${YELLOW}Verifica la configurazione in /etc/mysql/my.cnf${NC}"
    exit 1
fi

# 4. Carica credenziali da config
echo -e "\n${YELLOW}[4/6] Caricamento credenziali da app/Config/Database.php...${NC}"

# Estrai credenziali dal file PHP (semplificato)
DB_HOST=$(grep -oP "hostname.*?=>\s*'[^']*'" app/Config/Database.php | grep -oP "'[^']*'" | tail -1 | tr -d "'")
DB_NAME=$(grep -oP "database.*?=>\s*'[^']*'" app/Config/Database.php | grep -oP "'[^']*'" | tail -1 | tr -d "'")
DB_USER=$(grep -oP "username.*?=>\s*'[^']*'" app/Config/Database.php | grep -oP "'[^']*'" | tail -1 | tr -d "'")
DB_PASS=$(grep -oP "password.*?=>\s*'[^']*'" app/Config/Database.php | grep -oP "'[^']*'" | tail -1 | tr -d "'")

echo "  Host: $DB_HOST"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo "  Password: ${DB_PASS:0:3}***"

# 5. Test connessione
echo -e "\n${YELLOW}[5/6] Test connessione al database...${NC}"
if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" &> /dev/null; then
    echo -e "${GREEN}✓ Connessione al database riuscita${NC}"
else
    echo -e "${RED}✗ Impossibile connettersi al database${NC}"
    echo -e "${YELLOW}Possibili cause:${NC}"
    echo "  1. Credenziali errate"
    echo "  2. Database non esiste"
    echo "  3. Utente non ha permessi"
    echo ""
    echo -e "${YELLOW}Vuoi che provi a creare il database e l'utente? (y/n)${NC}"
    read -p "" -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${BLUE}Inserisci la password di root di MySQL:${NC}"
        mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
        echo -e "${GREEN}✓ Database e utente creati${NC}"
    else
        exit 1
    fi
fi

# 6. Verifica ed esegui migrations
echo -e "\n${YELLOW}[6/6] Verifica e esegui migrations...${NC}"
if [ -f "spark" ]; then
    echo -e "${BLUE}Stato migrations:${NC}"
    php spark migrate:status || true
    echo ""
    read -p "Vuoi eseguire le migrations? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php spark migrate
        echo -e "${GREEN}✓ Migrations completate${NC}"
    fi
else
    echo -e "${RED}✗ File spark non trovato${NC}"
fi

# Summary
echo -e "\n${BLUE}========================================${NC}"
echo -e "${GREEN}Setup completato!${NC}"
echo -e "${BLUE}========================================${NC}\n"

echo -e "${YELLOW}Prossimi passi:${NC}"
echo "  1. Verifica che le tabelle siano state create:"
echo "     mysql -u$DB_USER -p$DB_PASS $DB_NAME -e 'SHOW TABLES;'"
echo ""
echo "  2. Accedi alla dashboard analytics:"
echo "     http://localhost/analytics/dashboard"
echo ""
echo "  3. Controlla i log per eventuali errori:"
echo "     tail -f writable/logs/log-*.log"
echo ""
