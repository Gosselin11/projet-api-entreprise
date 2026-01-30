. Suivi des nouvelles entreprises (Aude - 11)

Ce projet PHP professionnel interroge l’API INSEE Sirene pour récupérer les nouvelles entreprises (établissements) du département de l’Aude (11) créées ou mises à jour durant le dernier mois. Le système les affiche sous forme de tableau dynamique et permet l’envoi d’un rapport e-mail automatisé.

1. Fonctionnalités détaillées
Récupération via API Sirene 3.11 : Utilisation du endpoint /siret pour une précision maximale sur les établissements.

Filtrage Géographique : Ciblage exclusif sur le département 11 (Aude) via les codes postaux.

Gestion de la Pagination : Support complet des curseurs d'API pour traiter des listes dépassant 1000 résultats.

Architecture POO (Programmation Orientée Objet) : Séparation du code en classes indépendantes pour faciliter l'évolution.

Sécurité des Données : Utilisation du standard .env pour masquer les clés API et mots de passe.

Affichage Navigateur : Tableau HTML complet avec liens externes vers les fiches Le Figaro Entreprises.

Rapport Email HTML : Génération d'un e-mail stylisé envoyé via le serveur SMTP de Gmail (PHPMailer).

Mode Hybride : Exécution via serveur web (index.php) ou en ligne de commande (CLI/Script .bat).

2. Structure et Organisation des Fichiers
   
L'organisation du projet suit les standards modernes de développement PHP :

projet-api-entreprise/
├── src/               
│   ├── SireneApi.php  # Gère la communication technique avec l'INSEE
│   └── Mailer.php     # Gère la configuration et l'envoi des e-mails
├── vendor/            # Bibliothèques externes (PHPMailer, Dotenv) gérées par Composer
├── .env               # VOTRE COFFRE-FORT (Clés API, SMTP). Jamais envoyé sur GitHub.
├── .env.example       # Modèle vide pour aider les autres développeurs.
├── .gitignore         # Liste d'exclusion pour Git (ignore .env et /vendor).
├── index.php          # Le point d'entrée principal (Logique métier et Affichage).
└── lancer_mail.bat    # Script Windows pour automatiser l'envoi.

3. Détails approfondis du fonctionnement
   
A. Configuration via variables d'environnement
Le script ne contient plus de données sensibles "en dur". Il utilise la bibliothèque phpdotenv pour charger les réglages depuis le fichier .env.

PHP

$api = new SireneApi($_ENV['INSEE_API_KEY'], $_ENV['DEPARTEMENT']);
Cela permet de changer de département ou de clé API sans jamais toucher au code source.

B. Calcul automatique de la période
Le script analyse les créations sur une fenêtre glissante. La date cible est calculée dynamiquement comme étant un mois avant la date du jour :

PHP

$dateCible = date('Y-m-d', strtotime('-1 month'));
L'idée est de récupérer les entreprises créées ou traitées à cette date précise ou sur la plage correspondante.

C. Appel à l’API INSEE Sirene
La classe SireneApi construit une requête complexe.

Le filtre : codePostalEtablissement:11* limite les résultats aux communes de l'Aude.

La requête q : Elle combine la date de création OU la date de dernier traitement pour ne rien rater des mises à jour administratives.

Le Tri : Les résultats sont triés par libelleCommuneEtablissement pour une lecture plus humaine.

D. Gestion de la pagination (Curseurs)
L’API Sirene ne renvoie pas toutes les données d'un coup si le volume est important. Elle utilise un système de "curseurs". Le script utilise une boucle do...while qui vérifie la présence d'un curseurSuivant dans l'en-tête de la réponse :

PHP

do {
    $data = $api->fetchEntreprises($dateCible, $curseurActuel);
    // Fusion des résultats dans le tableau global
    $etablissements = array_merge($etablissements, $data['etablissements']);
    // Mise à jour du curseur pour la page suivante
} while ($curseurActuel);
Cela garantit l'extraction de 100% des données, qu'il y ait 10 ou 5000 entreprises.

4. Système de Rapport Email
   
Construction du message
Pour éviter des e-mails trop lourds et illisibles, le script prépare une variable $lignesMail qui contient uniquement les 20 premières entreprises trouvées. Le corps du mail est un document HTML complet incluant :

Un résumé du nombre total d'entreprises trouvées.

Un tableau stylisé (Bordures, couleurs d'en-tête).

Des liens "Figaro" générés dynamiquement via le SIRET.

PHPMailer et SMTP
L'envoi est géré par la classe Mailer qui encapsule la configuration SMTP de Gmail :

Sécurité : Utilisation du chiffrement STARTTLS sur le port 587.

Authentification : Utilisation obligatoire d'un "Mot de passe d'application" pour contourner la double authentification de Gmail.

5. Installation pas à pas
   
   1 Cloner le dépôt
Bash

git clone https://github.com/votre-compte/projet-api-entreprise.git
cd projet-api-entreprise

   2 Installer les dépendances via Composer
Bash

composer require phpmailer/phpmailer vlucas/phpdotenv

   3 Configurer l'environnement (CRUCIAL)
Renommez .env.example en .env.

Éditez .env avec vos vraies informations :

Ini, TOML

INSEE_API_KEY=votre_cle_api
DESTINATAIRE_MAIL=votre@email.com
SMTP_USER=votre@gmail.com
SMTP_PASS=votre_code_application_gmail
DEPARTEMENT=11

6. Utilisation et Automatisation
   
- Exécution Web
Placez le projet dans votre dossier htdocs (XAMPP). Ouvrez : http://localhost/projet-api-entreprise/index.php Le tableau s'affiche et un bouton vert permet l'envoi manuel du mail.

- Exécution CLI (Ligne de commande)
Le script détecte s'il est lancé via le terminal. L'envoi du mail est alors automatique :

Bash

php index.php -- action=send

Automatisation Windows (.bat)

Le fichier lancer_mail.bat permet d'automatiser l'exécution via le Planificateur de tâches Windows.

Note : Vérifiez bien que le chemin vers php.exe correspond à votre installation (souvent C:\xampp\php\php.exe).

7. Sécurité & Bonnes pratiques
   
Protection des Secrets : Le fichier .gitignore contient .env. Cela garantit que vos mots de passe ne seront jamais visibles sur GitHub.

Limitation des mails : Le script limite l'affichage à 20 lignes dans l'e-mail pour éviter d'être considéré comme du spam.


1. Licence
   
Projet réalisé à des fins pédagogiques. L'utilisation des données SIRENE est soumise aux conditions générales de l'INSEE.