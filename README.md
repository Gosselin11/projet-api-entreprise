Suivi des nouvelles entreprises (Aude - 11)

Ce projet PHP interroge l’API INSEE Sirene 3.11 pour récupérer les nouvelles entreprises (établissements) du département de l’Aude (11), créées ou mises à jour à une date donnée, puis les affiche sous forme de tableau HTML, export PDF et rapport e‑mail.

1. Objectifs du projet
Suivre quotidiennement ou ponctuellement les créations/mises à jour d’établissements dans le département 11 (Aude).

Consolider les données de l’API Sirene dans un cache local JSON pour accélérer les consultations.

Produire des rapports exploitables : tableau HTML, PDF, et e‑mail HTML.

Faciliter l’automatisation (planification Windows via fichier .bat).

2. Fonctionnalités principales
Interrogation de l’API Sirene 3.11 : appel du service /siret avec filtres sur la date et le code postal.

Filtrage géographique sur le département 11 (code postal 11*).

Gestion de la pagination via les curseurs de l’API (curseur, curseurSuivant).

Mise en cache des résultats dans des fichiers JSON nommés cache/sirene_<DEPARTEMENT>_<date>.json.

Affichage Web complet (tableau HTML détaillé : SIRET, APE, NAF, domaines d’activité, liens Figaro/Annuaire).

Export PDF via Dompdf avec mise en page adaptée pour un rapport imprimable.

Envoi d’un e‑mail HTML récapitulatif (20 premières entreprises) via PHPMailer et SMTP Gmail (STARTTLS, port 587).

Enrichissement optionnel des données via l’API recherche-entreprises.api.gouv.fr pour compléter les noms et libellés d’activité.
​

Classification des entreprises par grand domaine d’activité (sections A à U de la NAF) via une fonction de mapping.

3. Technologies utilisées
PHP (procédural + classes simples).

PHPMailer pour l’envoi d’e‑mails SMTP.

vlucas/phpdotenv pour la gestion des variables d’environnement.

Dompdf pour la génération de PDF à partir de HTML.

API INSEE Sirene 3.11 (endpoint /siret).

API recherche-entreprises.api.gouv.fr pour enrichissement.
​

Serveur local type XAMPP (Apache + PHP).

4. Dépendances Composer
Les principales dépendances PHP du projet sont gérées via Composer :

json
{
    "require": {
        "phpmailer/phpmailer": "^7.0",
        "vlucas/phpdotenv": "^5.6",
        "dompdf/dompdf": "^3.1"
    }
}

phpmailer/phpmailer : création et envoi d’e‑mails SMTP complets (HTML, encodage UTF‑8, etc.).

vlucas/phpdotenv : chargement des variables d’environnement depuis le fichier .env dans $_ENV.

dompdf/dompdf : génération de PDF à partir de HTML/CSS (tableaux, styles, pagination, etc.).

5. Structure du projet

projet-api-entreprise/

├── src/

│   ├── SireneApi.php      # Appels à l’API Sirene (requêtes, filtres, pagination)

│   └── Mailer.php         # Envoi d’e-mails HTML via PHPMailer (SMTP Gmail)

├── cache/                 # Fichiers JSON de cache (générés automatiquement)

├── vendor/                # Dépendances Composer (PHPMailer, Dotenv, Dompdf, etc.)

├── .env                   # Variables sensibles (API, SMTP, département) - non versionné

├── .env.example           # Exemple de configuration (.env modèle)

├── .gitignore             # Ignore /vendor, .env, et fichiers temporaires

├── index.php              # Point d’entrée web + logique principale + tableau + envoi mail

├── generer_pdf.php        # Génération et téléchargement d’un PDF de rapport complet

├── annuaireApi.php        # Enrichissement via recherche-entreprises.api.gouv.fr

├── nomenclature.php       # Mapping Code APE -> grande catégorie d’activité (A..U)

└── lancer_mail.bat        # Script Windows pour exécution automatique (CLI)

6. Configuration (.env)
Le projet utilise phpdotenv pour charger les variables d’environnement.

Exemple .env (basé sur .env.example) :

INSEE_API_KEY=votre_cle_ici
DESTINATAIRE_MAIL=votre_mail_ici
SMTP_USER=votre_mail_smtp
SMTP_PASS=votre_mot_de_passe_application
DEPARTEMENT=11
INSEE_API_KEY : clé API Sirene obtenue sur le portail INSEE.

DESTINATAIRE_MAIL : e‑mail cible par défaut (optionnel).

SMTP_USER : adresse Gmail utilisée pour l’envoi.

SMTP_PASS : mot de passe d’application Gmail.

DEPARTEMENT : code département (par défaut 11 pour l’Aude).

7. Installation
   
Cloner le dépôt :

bash
git clone https://github.com/Gosselin11/projet-api-entreprise.git
cd projet-api-entreprise
Installer les dépendances via Composer :

bash
composer install

Ou, si le projet est recréé ailleurs :

composer require phpmailer/phpmailer:^7.0 vlucas/phpdotenv:^5.6 dompdf/dompdf:^3.1

Configurer .env comme décrit ci‑dessus.

Placer le projet dans htdocs (XAMPP), par exemple :

C:\xampp\htdocs\projet-api-entreprise\

1. Fonctionnement détaillé
   
8.1 Récupération des données (SireneApi.php)
La classe SireneApi :

stocke la clé API et le département,

construit le filtre codePostalEtablissement:<DEPARTEMENT>*,

interroge l’endpoint /siret avec une requête q combinant :

dateCreationEtablissement:$date

ou dateDernierTraitementEtablissement:$date*,

gère la pagination via le paramètre curseur.

La boucle dans index.php :

récupère la première page, lit header.total et header.curseurSuivant,

concatène etablissements dans un tableau global,

continue tant qu’un curseurSuivant est présent.

8.2 Cache local
Pour éviter de solliciter l’API en continu :

dossier cache/ créé automatiquement si nécessaire,

fichier JSON : cache/sirene_<DEPARTEMENT>_<date>.json,

structure : {"total": <nbTotalInsee>, "data": [ ... ]}.

Lorsque le cache existe (et éventuellement n’est pas expiré), les données sont chargées depuis le fichier, avec un message de statut.

8.3 Enrichissement des données (annuaireApi.php)
Sur clic du lien “Enrichir les données” :

charge le fichier de cache correspondant,

pour chaque établissement avec nom masqué (Non diffusable) ou sans libellé d’activité,

interroge https://recherche-entreprises.api.gouv.fr/search?q=<siret>,

remplace/complète le nom, le libellé d’activité principale et la section d’activité,

sauvegarde le cache mis à jour.
​

Une pause de 0,1 seconde entre requêtes limite la charge sur l’API.

8.4 Classification par domaines d’activité (nomenclature.php)
La fonction getNatureEntreprise($codeAPE) :

extrait le préfixe numérique du code APE,

s’appuie sur les tranches de codes pour associer l’entreprise à une section (A à U),

renvoie une description lisible (commerce, santé, etc.).

9. Interface Web (index.php)
Formulaire de sélection de date (date_debut), valeur par défaut = aujourd’hui - 1 mois.

Affichage d’un statut (données en cache ou appel API).

Formulaire d’envoi d’e‑mail (champ destinataire + bouton “Envoyer Mail”).

Lien pour générer le PDF (generer_pdf.php?date_debut=...).

Lien “Enrichir les données” pour déclencher annuaireApi.php.

Tableau HTML avec : Nom, Commune, CP, SIRET, Code APE, Code NAF, Dénomination NAF, Domaine d’activité, Date de traitement, liens Figaro/Annuaire.

10. Génération du PDF (generer_pdf.php)
Recharge les données depuis le cache pour la date demandée.

Génère un HTML complet (en‑tête, nombre total, tableau, pied de page).

Configure Dompdf (isHtml5ParserEnabled, isRemoteEnabled) et produit un PDF A4 portrait.

Le PDF est envoyé au navigateur via stream() avec un nom du type Rapport_SIRENE_Aude_YYYY-MM-DD.pdf.

11. Envoi d’e‑mail (Mailer.php + index.php)
La classe Mailer encapsule PHPMailer avec la configuration :

Host = smtp.gmail.com

SMTPAuth = true

Username = SMTP_USER

Password = SMTP_PASS

SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS

Port = 587

CharSet = UTF-8

L’e‑mail contient :

un résumé du nombre total d’entreprises,

un tableau HTML avec les 20 premières entrées,

des liens Figaro/Annuaire,

un bouton “Voir le détail complet” vers l’interface Web.

12. Utilisation
12.1 Via navigateur
Démarrer Apache (XAMPP).

Ouvrir : http://localhost/projet-api-entreprise/index.php.

Choisir une date et cliquer sur “Actualiser la liste”.

Optionnel : envoyer un mail, télécharger le PDF, enrichir les données.

12.2 Via CLI
En ligne de commande, l’envoi de mail peut être déclenché ainsi :

bash
php index.php -- action=send

12.3 Automatisation Windows

Le fichier lancer_mail.bat :

@echo off
"C:\xampp\php\php.exe" -f "C:\xampp\htdocs\projet-api-entreprise\index.php" -- action=send
Peut être planifié via le Planificateur de tâches Windows pour un envoi automatique.

13. Sécurité & bonnes pratiques
Ne jamais committer le fichier .env (déjà dans .gitignore).

Utiliser un mot de passe d’application Gmail.

Respecter les conditions d’utilisation de l’API Sirene et les limites de taux.

Limiter la taille des e‑mails (20 lignes) pour éviter les problèmes de délivrabilité.

14.  Licence
Projet réalisé à des fins pédagogiques.
L’utilisation des données Sirene est soumise aux conditions de l’INSEE et aux CGU des API publiques utilisées.