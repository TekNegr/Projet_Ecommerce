# 🚀 PS Ecommerce – Guide d’Installation avec Laravel Sail

Une plateforme e-commerce moderne construite avec **Laravel**, intégrant de l’IA pour la prédiction de statisfaction et un tableau de bord complet pour les vendeurs.  

---

## 📦 Prérequis
- **Linux/Ubuntu/WSL**
- **Docker Desktop** (dernière version) – [Télécharger ici](https://www.docker.com/products/docker-desktop/)
- **Git**
- **4 Go RAM minimum** (8 Go recommandé)
- **10 Go d’espace libre**
- (Optionnel mais conseillé) **Visual Studio Code**

---

## 🛠 Installation Pas-à-Pas (Recommandée)

### 1️⃣ Cloner le projet et préparer le fichier `.env`
```bash
git clone https://github.com/TekNegr/Projet_Ecommerce.git
cd Projet_Ecommerce
cp .env.example .env
```
⚠️ **Attention** : Ceci doit **ABSOLUMENT** être executer dans un terminal Linux pour que ça fonctionne et non un Terminal Powershell 

---

### 2️⃣ Lancer l’environnement avec Sail
```bash
./vendor/bin/sail up -d
```
💡 **Astuce** : Toujours lancer **Docker Desktop** avant cette commande.

---

### 3️⃣ Ouvrir un Shell Sail
```bash
./vendor/bin/sail shell
```
Toutes les commandes suivantes seront **tapées à l’intérieur** de ce shell.

---

### 4️⃣ Installer les dépendances PHP
```bash
composer install
```

---

### 5️⃣ Générer la clé de l’application
```bash
php artisan key:generate
```

---

### 6️⃣ Migrer et **remplir** la base de données
```bash
php artisan migrate --seed
```
✅ Cette commande crée la base et **insère les données de test**.

---

### 7️⃣ Installer les dépendances front-end
```bash
npm install
npm run dev
```

---

### 8️⃣ Quitter le Shell Sail
```bash
exit
```

---

## 🔑 Commandes Sail Utiles
```bash
./vendor/bin/sail up -d      # Démarrer l’application
./vendor/bin/sail down       # Arrêter l’application
./vendor/bin/sail shell      # Ouvrir un terminal dans le conteneur
```

---

## 💡 Dépannage rapide
- **Vider le cache Laravel :**
```bash
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear
```
- **Relancer le front si bloqué :**
```bash
npm run dev
```

## Connexions

Afin de vous connecter à l'application vous pouvez utiliser les identifiants suivant :
admin : admin@mail.com 
vendeur : seller@mail.com
client : customer@mail.com

les mots de passe sont tous "password"
