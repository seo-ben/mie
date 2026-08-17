#!/bin/bash

#############################################
#  Script de déploiement Laravel - cPanel   #
#  Exécuter manuellement si nécessaire      #
#############################################

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Début du déploiement...${NC}"
echo "================================================"

# ─── 1. Activer le mode maintenance ───
echo -e "${YELLOW}🔧 Activation du mode maintenance...${NC}"
php artisan down --retry=60 --refresh=15 || true

# ─── 2. Récupérer les dernières modifications ───
echo -e "${BLUE}🔄 Récupération des dernières modifications...${NC}"
git pull origin main

# ─── 3. Installer les dépendances Composer ───
echo -e "${BLUE}📦 Installation des dépendances Composer...${NC}"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ─── 4. Exécuter les migrations ───
echo -e "${BLUE}🗄️ Exécution des migrations...${NC}"
php artisan migrate --force

# ─── 5. Vider et reconstruire les caches ───
echo -e "${BLUE}🧹 Nettoyage et reconstruction des caches...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ─── 6. Lien de stockage ───
echo -e "${BLUE}🔗 Mise à jour du lien de stockage...${NC}"
php artisan storage:link 2>/dev/null || true

# ─── 7. Désactiver le mode maintenance ───
echo -e "${GREEN}✅ Désactivation du mode maintenance...${NC}"
php artisan up

echo "================================================"
echo -e "${GREEN}🎉 Déploiement terminé avec succès !${NC}"
echo "Heure : $(date)"
