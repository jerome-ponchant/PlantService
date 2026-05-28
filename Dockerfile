FROM php:8.4.10-apache

# 1. Installation des extensions système nécessaires à Symfony (zip, intl, etc.)
RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl zip pdo pdo_mysql

# 2. Installation des outils graphiques, de VNC et des utilitaires (ex: un terminal et un navigateur)
RUN apt-get update && apt-get install -y \
    xvfb \
    x11vnc \
    fluxbox \
    novnc \
    websockify \
    rxvt-unicode \
    firefox-esr \
    pcmanfm \
    geany \
    && apt-get clean

# 2. Activation du module de réécriture d'Apache (indispensable pour le .htaccess Symfony)
RUN a2enmod rewrite

# 3. Récupération de Composer depuis son image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 5. Configuration d'Apache (DocumentRoot et activation des .htaccess)
RUN sed -i 's|/var/www/html|/var/www|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|/var/www/html|/var/www|g' /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# === LIGNES À RAJOUTER CORRIGÉES ===
# 6. Création du script de démarrage (avec gestion propre du display et pause)
RUN echo '#!/bin/bash\n\
# 1. Lancer le serveur X virtuel\n\
Xvfb :1 -screen 0 1280x1024x24 &\n\
\n\
# Attendre un court instant que Xvfb soit initialisé\n\
sleep 1\n\
\n\
# Définir la variable DISPLAY pour tous les processus suivants\n\
export DISPLAY=:1\n\
\n\
# 2. Lancer le gestionnaire de fenêtres et le serveur VNC\n\
fluxbox &\n\
x11vnc -display :1 -nopw -forever -shared &\n\
\n\
# 3. Lancer le pont Web/VNC pour noVNC\n\
websockify --web /usr/share/novnc 6080 localhost:5900 &\n\
\n\
# 4. Lancer Apache au premier plan (bloquant)\n\
apache2-foreground\n\
' > /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
