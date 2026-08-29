# Textes encore ecrits en dur

**761 occurrences dans 136 fichiers.**

Releve automatique. Un texte est retenu s'il est lu par un utilisateur :
texte affiche, attribut visible (`title`, `alt`, `aria-label`, `placeholder`),
propriete de composant (`libelle=`, `titre=`), message flash, message de
validation, sujet ou corps d'e-mail. Les commentaires, les traces SVG, les
classes CSS et les identifiants techniques sont ecartes.

---

## Repartition

| Zone | Occurrences | Fichiers |
|---|---:|---:|
| vues / admin | 266 | 12 |
| vues / emails | 177 | 29 |
| app/Console/Commands | 116 | 23 |
| vues / (racine) | 45 | 2 |
| app/Http/Requests | 35 | 10 |
| vues / components | 32 | 17 |
| app/Http/Controllers | 21 | 11 |
| vues / profile | 16 | 5 |
| vues / layouts | 9 | 3 |
| vues / landing | 6 | 3 |
| vues / public | 6 | 2 |
| app/Services/RapportQuotidien.php | 5 | 1 |
| vues / auth | 4 | 4 |
| vues / design-system | 4 | 1 |
| app/Services/NotificationService.php | 3 | 1 |
| app/Services/Admin | 3 | 2 |
| vues / legal | 2 | 1 |
| vues / vendor | 2 | 1 |
| app/Models/User.php | 2 | 1 |
| vues / account | 1 | 1 |
| vues / dashboard | 1 | 1 |
| vues / notifications | 1 | 1 |
| vues / profil | 1 | 1 |
| vues / statistiques | 1 | 1 |
| app/Mail/BaseMailable.php | 1 | 1 |
| app/Support/DestinatairesEquipe.php | 1 | 1 |

---

## Detail par fichier

### vues / admin


**`resources/views/admin/clients/show.blade.php`** — 46

- L15 · *attribut subtitle* · Fiche client
- L20 · *attribut libelle* · Désactiver le profil
- L21 · *attribut confirmation* · Désactiver
- L24 · *attribut titre* · Désactiver ce profil
- L25 · *attribut texte* · La carte cesse immédiatement d'être accessible publiquement. Le contenu n'est ni modifié n
- L30 · *attribut libelle* · Réactiver le profil
- L31 · *attribut confirmation* · Lever la désactivation
- L33 · *attribut titre* · Lever la désactivation
- L34 · *attribut texte* · Le profil restera en brouillon : c'est à son propriétaire de le republier.
- L41 · *attribut libelle* · Débloquer le compte
- L42 · *attribut confirmation* · Débloquer
- L44 · *attribut titre* · Débloquer ce compte
- L45 · *texte* · blocked_reason ?? 'non renseigné').'.'" />
- L49 · *attribut libelle* · Bloquer le compte
- L53 · *attribut titre* · Bloquer ce compte
- L54 · *attribut texte* · Le client ne pourra plus se connecter et ses sessions ouvertes seront fermées. Sa carte pu
- L59 · *attribut aria-label* · Fil d'ariane
- L84 · *texte* · Compte bloqué
- L86 · *texte* · Compte actif
- L90 · *texte* · isActive() ? 'success' : 'secondary'">
- L97 · *texte* · Inscrit le
- L105 · *texte* · Motif du blocage :
- L116 · *texte* · Identité professionnelle
- L121 · *attribut title* · Aucun profil créé
- L122 · *attribut message* · Ce compte existe mais son propriétaire n'a pas encore rempli sa carte.
- L125 · *texte* · Nom complet
- L128 · *texte* · Identifiant public
- L137 · *texte* · Modèle
- L138 · *texte* · État
- L141 · *texte* · Désactivé
- L143 · *texte* · Publié
- L148 · *texte* · Créé le
- L151 · *texte* · Motif de désactivation
- L170 · *attribut confirmation* · Prolonger l'abonnement
- L172 · *attribut titre* · Prolonger manuellement l'abonnement
- L176 · *texte* · Nombre de jours
- L183 · *texte* · Au-delà de
- L183 · *texte* · jours, ce n'est plus un geste
- L184 · *texte* · commercial : passez par une formule, qui laisse une
- L209 · *attribut title* · Aucun abonnement
- … et 6 autres

**`resources/views/admin/settings/index.blade.php`** — 38

- L14 · *attribut title* · Paramètres de la plateforme
- L15 · *attribut subtitle* · Gérer les offres tarifaires proposées aux clients.
- L17 · *attribut aria-label* · Sections des paramètres
- L32 · *attribut title* · Aucun réglage modifiable ici
- L44 · *texte* · Offres actuelles
- L53 · *texte* · Retirée
- L74 · *attribut title* · Aucune formule
- L75 · *attribut message* · Créez une première formule pour ouvrir la vente.
- L82 · *texte* · Créer une nouvelle formule
- L88 · *texte* · Nom de la formule
- L94 · *texte* · Identifiant technique
- L100 · *texte* · Définitif : il est inscrit dans chaque paiement et ne pourra plus être modifié.
- L106 · *texte* · Prix en FCFA
- L110 · *texte* · Nombre entier : le franc CFA n'a pas de subdivision en circulation.
- L115 · *texte* · Périodicité
- L125 · *texte* · Créer la formule
- L134 · *attribut title* · Aucune formule à modifier
- L135 · *attribut message* · Créez d'abord une formule dans la colonne de gauche.
- L138 · *texte* · Éditeur de formule
- L148 · *texte* · Nom de la formule
- L155 · *texte* · Identifiant technique
- L159 · *texte* · slug }}" readonly disabled>
- L161 · *texte* · Non modifiable : cet identifiant est inscrit dans
- L162 · *texte* · chaque paiement déjà encaissé.
- L167 · *texte* · Prix en FCFA
- L169 · *texte* · price_fcfa) }}"
- L172 · *texte* · Les abonnements en cours ne sont pas touchés : le
- L173 · *texte* · nouveau tarif s'applique aux souscriptions suivantes.
- L179 · *texte* · Périodicité
- L192 · *texte* · duration_days }}" selected>
- L200 · *texte* · Éléments inclus
- L215 · *attribut placeholder* · Ajouter un élément…
- L216 · *attribut aria-label* · Nouvel élément inclus
- L218 · *attribut placeholder* · Ajouter un élément…
- L219 · *attribut aria-label* · Nouvel élément inclus
- L222 · *texte* · Videz une ligne pour retirer l'élément correspondant.
- L228 · *texte* · is_active))>
- L229 · *texte* · Formule proposée à la vente

**`resources/views/admin/system-health.blade.php`** — 27

- L1 · *attribut title* · État système
- L4 · *texte* · État système
- L12 · *texte* · job(s) en échec. Inspecte
- L13 · *texte* · php artisan queue:retry all
- L19 · *texte* · File « mail » engorgée (
- L19 · *texte* · en attente). Vérifie que le worker tourne.
- L26 · *texte* · File « mail »
- L33 · *texte* · Total jobs
- L40 · *texte* · Jobs échoués
- L41 · *texte* · 0 ? 'text-danger' : '' }}">
- L42 · *texte* · à relancer
- L62 · *texte* · Les e-mails ne partent probablement pas.
- L63 · *texte* · Le pilote de file est
- L64 · *texte* · . Le plan gratuit de Render
- L64 · *texte* · worker exécutant
- L65 · *texte* · n'en fait pas tourner : les messages sont écrits dans la table
- L66 · *texte* · et jamais repris — sans la moindre erreur.
- L68 · *texte* · Correction immédiate : passer
- L68 · *texte* · QUEUE_CONNECTION
- L69 · *texte* · dans les variables d'environnement, puis
- L70 · *texte* · redéployer. L'envoi se fera dans la requête — plus lent d'une
- L71 · *texte* · seconde ou deux, mais il aboutira.
- L83 · *texte* · Derniers e-mails envoyés
- L86 · *texte* · aujourd'hui
- L92 · *attribut title* · Aucun e-mail enregistré
- L115 · *texte* · Envoyé
- L134 · *texte* · Rafraîchi au chargement de la page. Aucune donnée temps réel côté navigateur.

**`resources/views/admin/profiles/index.blade.php`** — 23

- L15 · *attribut title* · Liste des profils
- L16 · *attribut subtitle* · Suivre et contrôler les cartes numériques publiées par les clients.
- L23 · *texte* · Exporter CSV
- L35 · *attribut placeholder* · Nom ou identifiant public…
- L39 · *texte* · État du profil
- L41 · *texte* · Tous les états
- L49 · *texte* · Modèle utilisé
- L51 · *texte* · Tous les modèles
- L61 · *texte* · Réinitialiser
- L72 · *attribut vide* · Les cartes créées par vos clients apparaîtront ici.
- L81 · *texte* · Identifiant public
- L82 · *texte* · Modèle
- L83 · *texte* · État
- L122 · *texte* · Publié
- L125 · *texte* · Désactivé
- L147 · *attribut libelle* · Réactiver
- L148 · *attribut confirmation* · Lever la désactivation
- L150 · *attribut titre* · Lever la désactivation
- L153 · *texte* · .'. Le profil restera en brouillon : c\'est à son propriétaire de le republier.'" />
- L157 · *attribut libelle* · Désactiver
- L158 · *attribut confirmation* · Désactiver ce profil
- L161 · *attribut titre* · Désactiver ce profil
- L162 · *attribut texte* · La carte cesse immédiatement d'être accessible publiquement. Le contenu n'est ni modifié n

**`resources/views/admin/statistics.blade.php`** — 22

- L13 · *attribut title* · Statistiques d'usage
- L14 · *attribut subtitle* · Ce que les cartes produisent réellement : consultations, scans et enregistrements.
- L19 · *texte* · Période
- L20 · *attribut onchange* · this.form.submit()
- L28 · *php embarqué* · Interactions totales
- L30 · *php embarqué* · Scans de QR Code
- L33 · *texte* · Exporter CSV
- L57 · *texte* · % des interactions
- L67 · *texte* · Interactions par jour
- L68 · *texte* · Toutes interactions
- L73 · *attribut title* · Aucune interaction sur la période
- L74 · *attribut message* · Aucune carte n'a été consultée ni scannée. Changez de période, ou vérifiez que des profils
- L97 · *texte* · Cartes les plus consultées
- L98 · *texte* · Tous les profils
- L103 · *attribut title* · Aucune carte consultée
- L104 · *attribut message* · Le classement apparaîtra dès la première consultation.
- L131 · *php embarqué* · Publiés
- L133 · *php embarqué* · Désactivés
- L146 · *texte* · État des profils
- L173 · *texte* · Modèles utilisés
- L177 · *attribut title* · Aucune carte publiée
- L178 · *attribut message* · La répartition apparaîtra à la première publication.

**`resources/views/admin/overview.blade.php`** — 21

- L11 · *attribut title* · Vue d'ensemble
- L12 · *attribut subtitle* · Suivi de l'activité de la plateforme aujourd'hui.
- L19 · *texte* · Période
- L20 · *attribut onchange* · this.form.submit()
- L61 · *texte* · — sur la période précédente
- L65 · *texte* · sur la période précédente
- L77 · *texte* · Tendance des inscriptions
- L81 · *texte* · Nouveaux comptes
- L87 · *attribut title* · Aucune inscription sur la période
- L88 · *attribut message* · Le graphique apparaîtra dès la première création de compte.
- L128 · *texte* · Moyens de paiement
- L132 · *attribut title* · Aucun paiement encaissé
- L133 · *attribut message* · La répartition apparaîtra au premier encaissement.
- L162 · *texte* · Dernières inscriptions
- L163 · *texte* · Voir tout
- L183 · *attribut title* · Aucune inscription
- L184 · *attribut message* · Les nouveaux comptes apparaîtront ici.
- L190 · *texte* · Derniers paiements
- L191 · *texte* · Voir tout
- L214 · *attribut title* · Aucun paiement
- L215 · *attribut message* · Les encaissements apparaîtront ici.

**`resources/views/admin/payments/index.blade.php`** — 21

- L15 · *attribut title* · Liste des paiements
- L16 · *attribut subtitle* · Suivi des transactions financières, avec vérification manuelle auprès de l'opérateur.
- L19 · *php embarqué* · Réussis
- L20 · *php embarqué* · En attente
- L21 · *php embarqué* · Échoués
- L23 · *texte* · Exporter CSV
- L30 · *attribut aria-label* · Filtrer par statut
- L57 · *texte* · Moyen de paiement
- L59 · *texte* · Tous les moyens
- L71 · *texte* · Période
- L95 · *attribut vide* · Les paiements apparaîtront ici dès le premier encaissement.
- L103 · *texte* · Référence et date
- L109 · *texte* · Vérification
- L136 · *texte* · Compte supprimé
- L172 · *attribut libelle* · Vérifier
- L173 · *attribut titre* · Vérifier ce paiement auprès de l'opérateur
- L176 · *texte* · . Si elle
- L177 · *texte* · confirme l'encaissement, l'abonnement est ouvert
- L178 · *texte* · immédiatement. L'opération est sans effet si le paiement
- L179 · *texte* · a déjà été encaissé." />
- L195 · *texte* · Total encaissé sur ce filtre :

**`resources/views/admin/clients/index.blade.php`** — 18

- L13 · *attribut title* · Liste des clients
- L14 · *attribut subtitle* · Gérer et consulter la totalité des utilisateurs inscrits.
- L21 · *texte* · Exporter CSV
- L34 · *attribut placeholder* · Nom, e-mail ou téléphone…
- L51 · *texte* · Statut du compte
- L53 · *texte* · Tous les statuts
- L54 · *texte* · Compte actif
- L55 · *texte* · Compte bloqué
- L56 · *texte* · Avec abonnement
- L57 · *texte* · Sans abonnement
- L66 · *texte* · Réinitialiser
- L78 · *attribut vide* · Les comptes clients apparaîtront ici dès la première inscription.
- L88 · *texte* · Téléphone
- L112 · *texte* · Compte bloqué
- L126 · *texte* · Aucun profil
- L128 · *texte* · Désactivé
- L130 · *texte* · Publié
- L140 · *texte* · Essai gratuit

**`resources/views/admin/cartes/index.blade.php`** — 16

- L14 · *attribut title* · Cartes à produire
- L15 · *attribut subtitle* · Production par lots, export imprimeur et suivi des expéditions.
- L22 · *texte* · Export imprimeur
- L37 · *texte* · Seuil atteint — lancez un lot
- L46 · *texte* · Délai annoncé dépassé
- L52 · *texte* · renouvellent au 2ᵉ trimestre
- L53 · *texte* · client(s) payant(s)
- L60 · *texte* · encaissés −
- L67 · *attribut aria-label* · Filtrer par état
- L86 · *attribut vide* · Les commandes de cartes apparaîtront ici dès la première activation payée.
- L100 · *texte* · État
- L125 · *texte* · user_id) }}">
- L141 · *texte* · Adresse manquante
- L156 · *texte* · Créer un lot avec la sélection
- L159 · *texte* · Seules les commandes en attente et dont l'adresse est complète sont incluses.
- L170 · *texte* · Faire passer le lot

**`resources/views/admin/audit/index.blade.php`** — 13

- L14 · *attribut title* · Journal d'audit
- L15 · *attribut subtitle* · Traçabilité complète des actions administratives sensibles.
- L22 · *texte* · Exporter CSV
- L34 · *attribut placeholder* · Action, cible ou motif…
- L38 · *texte* · Période
- L49 · *texte* · Tous les administrateurs
- L57 · *texte* · Type d'action
- L59 · *texte* · Tous les types
- L69 · *texte* · Réinitialiser
- L79 · *attribut nom* · entrée
- L80 · *attribut vide* · Les actions d'administration seront consignées ici.
- L88 · *texte* · Date et heure
- L167 · *texte* · entrée

**`resources/views/admin/subscriptions/index.blade.php`** — 12

- L20 · *attribut subtitle* · Suivre les échéances et l'état des souscriptions en cours.
- L27 · *texte* · Exporter CSV
- L31 · *attribut aria-label* · Filtrer par statut
- L53 · *texte* · Toutes les formules
- L63 · *texte* · Échéance
- L65 · *texte* · Toutes les échéances
- L66 · *texte* · Échoit sous 3 jours
- L67 · *texte* · Échoit sous 7 jours
- L68 · *texte* · Échoit sous 30 jours
- L87 · *attribut vide* · Les abonnements apparaîtront ici dès la première souscription.
- L97 · *texte* · Début
- L98 · *texte* · Échéance

**`resources/views/admin/templates/index.blade.php`** — 9

- L12 · *attribut title* · Modèles de carte
- L13 · *attribut subtitle* · Gérer les gabarits visuels proposés aux clients à la création de leur carte.
- L20 · *texte* · Un nouveau modèle se crée en dupliquant un existant, puis en le relisant.
- L24 · *attribut aria-label* · Filtrer les modèles
- L42 · *attribut title* · Aucun modèle
- L43 · *attribut message* · Aucun gabarit ne correspond à cet onglet.
- L71 · *texte* · le modèle
- L79 · *texte* · Par défaut
- L102 · *texte* · Définir par défaut

### vues / emails


**`resources/views/emails/client/subscription-expiring.blade.php`** — 11

- L6 · *texte* · aujourd'hui
- L8 · *texte* · Votre abonnement
- L11 · *texte* · Votre abonnement
- L17 · *texte* · Passé cette date, le lien public de votre carte cessera de répondre :
- L18 · *texte* · les personnes qui l'ouvriront, ou qui scanneront votre QR Code, ne
- L19 · *texte* · verront plus vos coordonnées.
- L23 · *texte* · Rien n'est supprimé.
- L23 · *texte* · Votre carte, vos coordonnées et
- L24 · *texte* · votre lien sont conservés en l'état. Un renouvellement les remet en
- L25 · *texte* · ligne immédiatement, sans rien ressaisir et sans changer d'adresse —
- L26 · *texte* · les cartes déjà imprimées restent valables.

**`resources/views/emails/client/welcome.blade.php`** — 11

- L12 · *texte* · Votre adresse est confirmée et votre compte est actif. Votre essai
- L13 · *texte* · jours a démarré aujourd'hui.
- L15 · *texte* · Il court jusqu'au
- L20 · *texte* · Il reste une étape : créer votre carte. Comptez cinq minutes — nom,
- L21 · *texte* · fonction, coordonnées, et le choix d'un modèle. Vous obtenez ensuite un
- L22 · *texte* · lien et un QR Code à partager immédiatement.
- L38 · *texte* · Un groupe WhatsApp est réservé à nos clients.
- L39 · *texte* · Entraide, questions, et réponses rapides de notre équipe.
- L40 · *texte* · Rejoindre le groupe
- L47 · *texte* · Pendant l'essai, votre carte est publiable et consultable sans aucun
- L48 · *texte* · paiement. Aucun moyen de paiement ne vous est demandé.

**`resources/views/emails/client/payment-failed.blade.php`** — 10

- L5 · *texte* · Aucune somme n'a été prélevée.
- L5 · *texte* · Votre paiement de
- L6 · *texte* · FCFA pour la formule
- L6 · *texte* · n'est pas allé à son
- L7 · *texte* · terme, et votre abonnement n'a pas été modifié.
- L11 · *texte* · Cela arrive le plus souvent pour une raison simple : solde insuffisant
- L12 · *texte* · au moment de l'opération, code de confirmation non saisi à temps, ou
- L13 · *texte* · page fermée avant la fin. Vous pouvez réessayer immédiatement.
- L21 · *texte* · Si une somme apparaissait malgré tout sur votre compte, répondez à ce
- L22 · *texte* · message : nous la retrouvons et nous la traitons.

**`resources/views/emails/client/profile-reminder.blade.php`** — 10

- L6 · *texte* · Votre carte est enregistrée, mais elle n'est pas encore en ligne :
- L7 · *texte* · son lien ne répond donc à personne. Il ne manque qu'un clic pour la
- L12 · *texte* · Votre carte est toujours enregistrée sans être publiée. Si quelque
- L13 · *texte* · chose vous a arrêté — un champ qui ne convient pas, une photo qui
- L14 · *texte* · ne passe pas, un doute sur le rendu — répondez simplement à ce
- L15 · *texte* · message : nous regardons avec vous.
- L20 · *texte* · Publier ne coûte rien pendant votre essai gratuit, et reste réversible :
- L21 · *texte* · vous pouvez retirer votre carte à tout moment.
- L29 · *texte* · C'est notre
- L29 · *texte* · rappel à ce sujet.

**`resources/views/emails/client/subscription-expired.blade.php`** — 10

- L5 · *texte* · . Depuis cette
- L5 · *texte* · Votre abonnement est arrivé à échéance le
- L6 · *texte* · date, le lien public de votre carte ne répond plus.
- L10 · *texte* · Rien n'a été supprimé :
- L10 · *texte* · Vos données sont intactes.
- L11 · *texte* · votre carte, vos coordonnées, vos liens et votre QR Code sont conservés.
- L12 · *texte* · Votre adresse publique reste la même, donc les cartes que vous avez
- L13 · *texte* · déjà imprimées ou distribuées redeviendront valables telles quelles.
- L17 · *texte* · Un renouvellement remet tout en ligne en quelques secondes.
- L26 · *texte* · Adresse conservée pour votre carte :

**`resources/views/emails/registration/already-registered.blade.php`** — 10

- L2 · *texte* · Vous avez déjà un compte
- L5 · *texte* · Une demande d'inscription vient d'être faite avec cette adresse e-mail,
- L6 · *texte* · déjà associée à un compte
- L7 · *texte* · Aucun nouveau compte n'a été créé.
- L11 · *texte* · Si c'était vous, connectez-vous simplement :
- L17 · *texte* · Me connecter
- L22 · *texte* · Mot de passe oublié ?
- L23 · *texte* · Réinitialisez-le ici
- L27 · *texte* · Si vous n'êtes pas à l'origine de cette demande, ignorez ce message :
- L28 · *texte* · votre compte reste inchangé.

**`resources/views/emails/client/payment-succeeded_text.blade.php`** — 9

- L3 · *texte* · FCFA est encaissé et votre abonnement est actif. Conservez ce message : il vaut reçu.
- L3 · *texte* · Votre paiement de
- L5 · *texte* · Référence :
- L8 · *texte* · Moyen de paiement :
- L11 · *texte* · Valable jusqu'au :
- L15 · *texte* · Votre lien à partager :
- L18 · *texte* · Votre espace :
- L22 · *texte* · Votre QR Code et le fichier prêt pour l'impression sont joints à ce message. Ils restent é
- L24 · *texte* · Une question sur ce paiement ? Répondez à ce message en citant la référence ci-dessus.

**`resources/views/emails/auth/reset-password.blade.php`** — 8

- L2 · *texte* · Réinitialisation de mot de passe
- L5 · *texte* · Vous avez demandé la réinitialisation du mot de passe de votre compte
- L6 · *texte* · . Cliquez sur le bouton ci-dessous pour en choisir un nouveau.
- L12 · *texte* · Choisir un nouveau mot de passe
- L17 · *texte* · Ce lien est valable
- L21 · *texte* · Si le bouton ne s'affiche pas, copiez ce lien dans votre navigateur :
- L26 · *texte* · Si vous n'êtes pas à l'origine de cette demande, ignorez ce message :
- L27 · *texte* · votre mot de passe restera inchangé.

**`resources/views/emails/client/password-changed.blade.php`** — 8

- L5 · *texte* · Le mot de passe de votre compte a été modifié le
- L9 · *texte* · , il n'y a rien à faire : ce message
- L9 · *texte* · Si c'est bien vous
- L14 · *texte* · Si ce n'est pas vous
- L15 · *texte* · Demandez immédiatement un nouveau mot de passe pour reprendre la main :
- L28 · *texte* · Adresse IP à l'origine de la modification :
- L33 · *texte* · Ce message de sécurité est envoyé à chaque changement de mot de passe et
- L34 · *texte* · ne peut pas être désactivé.

**`resources/views/emails/client/payment-succeeded.blade.php`** — 8

- L5 · *texte* · Votre paiement de
- L5 · *texte* · est encaissé et
- L6 · *texte* · votre abonnement est actif. Conservez ce message : il vaut reçu.
- L22 · *texte* · Votre lien à partager
- L30 · *texte* · Votre QR Code et le fichier prêt pour l'impression sont joints à ce
- L31 · *texte* · message. Ils restent également téléchargeables depuis votre espace.
- L35 · *texte* · Une question sur ce paiement ? Répondez à ce message en citant la
- L36 · *texte* · référence ci-dessus.

**`resources/views/emails/registration/confirm.blade.php`** — 8

- L5 · *texte* · Plus qu'une étape pour activer votre présence professionnelle numérique
- L6 · *texte* · et démarrer votre essai gratuit de 15 jours : confirmez votre adresse e-mail.
- L12 · *texte* · Confirmer mon inscription
- L17 · *texte* · Ce lien est valable
- L17 · *texte* · minutes. Tant que vous ne l'avez pas
- L18 · *texte* · ouvert, aucun compte n'est créé.
- L22 · *texte* · Si le bouton ne s'affiche pas, copiez ce lien dans votre navigateur :
- L27 · *texte* · Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message.

**`resources/views/emails/client/subscription-expiring_text.blade.php`** — 7

- L4 · *texte* · Votre abonnement
- L4 · *texte* · se termine AUJOURD'HUI.
- L6 · *texte* · Votre abonnement
- L6 · *texte* · se termine DEMAIN, le
- L8 · *texte* · Votre abonnement
- L8 · *texte* · jours, le
- L15 · *texte* · Renouveler mon abonnement :

**`resources/views/emails/client/welcome_text.blade.php`** — 6

- L4 · *texte* · Votre adresse est confirmée et votre compte est actif. Votre essai gratuit de
- L4 · *texte* · jours a démarré aujourd'hui.
- L6 · *texte* · Il court jusqu'au
- L11 · *texte* · Créer ma carte :
- L15 · *texte* · Un groupe WhatsApp est réservé à nos clients — entraide, questions, et réponses rapides de
- L19 · *texte* · Pendant l'essai, votre carte est publiable et consultable sans aucun paiement. Aucun moyen

**`resources/views/emails/registration/already-registered_text.blade.php`** — 6

- L1 · *texte* · Vous avez deja un compte
- L3 · *texte* · Une demande d'inscription vient d'etre faite avec cette adresse e-mail,
- L5 · *texte* · Aucun nouveau compte n'a ete cree.
- L7 · *texte* · Si c'etait vous, connectez-vous simplement :
- L10 · *texte* · Mot de passe oublie ? Reinitialisez-le ici :
- L13 · *texte* · Si vous n'etes pas a l'origine de cette demande, ignorez ce message :

**`resources/views/emails/admin/contact_text.blade.php`** — 5

- L1 · *texte* · FORMULAIRE DE CONTACT
- L7 · *texte* · Téléphone :
- L9 · *texte* · Compte client :
- L10 · *texte* · Reçu le :
- L18 · *texte* · Répondez directement à ce message : votre réponse partira vers

**`resources/views/emails/client/password-changed_text.blade.php`** — 5

- L3 · *texte* · Le mot de passe de votre compte a été modifié le
- L5 · *texte* · SI C'EST BIEN VOUS, il n'y a rien à faire : ce message est une simple confirmation.
- L7 · *texte* · SI CE N'EST PAS VOUS, votre compte est en danger. Demandez immédiatement un nouveau mot de
- L11 · *texte* · Adresse IP à l'origine de la modification :
- L14 · *texte* · Ce message de sécurité est envoyé à chaque changement de mot de passe et ne peut pas être 

**`resources/views/emails/client/payment-failed_text.blade.php`** — 5

- L3 · *texte* · AUCUNE SOMME N'A ÉTÉ PRÉLEVÉE. Votre paiement de
- L3 · *texte* · FCFA pour la formule
- L3 · *texte* · n'est pas allé à son terme, et votre abonnement n'a pas été modifié.
- L7 · *texte* · Réessayer le paiement :
- L10 · *texte* · Si une somme apparaissait malgré tout sur votre compte, répondez à ce message : nous la re

**`resources/views/emails/client/profile-published.blade.php`** — 5

- L5 · *texte* · Votre carte est publiée. Toute personne qui ouvre le lien ci-dessous
- L6 · *texte* · voit vos coordonnées et peut les enregistrer dans son téléphone en un
- L13 · *texte* · Votre lien à partager
- L18 · *texte* · Depuis votre espace, vous pouvez télécharger le QR Code de cette carte
- L19 · *texte* · et le fichier prêt pour l'impression.

**`resources/views/emails/client/profile-reminder_text.blade.php`** — 5

- L4 · *texte* · Votre carte est enregistrée, mais elle n'est pas encore en ligne : son lien ne répond donc
- L9 · *texte* · Publier ne coûte rien pendant votre essai gratuit, et reste réversible : vous pouvez retir
- L11 · *texte* · Publier ma carte :
- L14 · *texte* · C'est notre
- L14 · *texte* · rappel à ce sujet.

**`resources/views/emails/client/subscription-expired_text.blade.php`** — 5

- L3 · *texte* · . Depuis cette date, le lien public de votre carte ne répond plus.
- L3 · *texte* · Votre abonnement est arrivé à échéance le
- L7 · *texte* · Un renouvellement remet tout en ligne en quelques secondes.
- L9 · *texte* · Réactiver ma carte :
- L13 · *texte* · Adresse conservée pour votre carte :

**`resources/views/emails/registration/confirm_text.blade.php`** — 5

- L3 · *texte* · Plus qu'une etape pour activer votre presence professionnelle numerique
- L4 · *texte* · et demarrer votre essai gratuit de 15 jours : confirmez votre adresse e-mail
- L5 · *texte* · en ouvrant ce lien (valable
- L9 · *texte* · Tant que ce lien n'est pas ouvert, aucun compte n'est cree.
- L11 · *texte* · Si vous n'etes pas a l'origine de cette demande, ignorez ce message.

**`resources/views/emails/auth/reset-password_text.blade.php`** — 4

- L1 · *texte* · Reinitialisation de mot de passe
- L3 · *texte* · Vous avez demande la reinitialisation du mot de passe de votre compte
- L4 · *texte* · . Ouvrez ce lien (valable
- L9 · *texte* · Si vous n'etes pas a l'origine de cette demande, ignorez ce message :

**`resources/views/emails/layout.blade.php`** — 3

- L44 · *texte* · — plateforme d'identité
- L45 · *texte* · professionnelle numérique.
- L46 · *texte* · Cet e-mail vous est envoyé car une action a été effectuée avec votre adresse.

**`resources/views/emails/admin/contact.blade.php`** — 3

- L7 · *texte* · FORMULAIRE DE CONTACT
- L30 · *texte* · : votre réponse partira
- L30 · *texte* · Répondez directement à ce message

**`resources/views/emails/client/profile-published_text.blade.php`** — 3

- L3 · *texte* · Votre carte est publiée. Toute personne qui ouvre le lien ci-dessous voit vos coordonnées 
- L5 · *texte* · Votre lien à partager :
- L8 · *texte* · Depuis votre espace, vous pouvez télécharger le QR Code de cette carte et le fichier prêt 

**`resources/views/emails/admin/alerte.blade.php`** — 2

- L3 · *texte* · estUrgent() ? '#FEF2F2' : '#F1F5F9' }};border-radius:8px;">
- L25 · *texte* · Message automatique destiné à l'équipe. Il n'a pas été envoyé au client.

**`resources/views/emails/admin/alerte_text.blade.php`** — 2

- L9 · *texte* · Ouvrir dans l'administration :
- L13 · *texte* · Message automatique destiné à l'équipe. Il n'a pas été envoyé au client.

**`resources/views/emails/partials/details.blade.php`** — 2

- L15 · *texte* · last ? 'none' : '1px solid #E6EAF0' }};">
- L18 · *texte* · last ? 'none' : '1px solid #E6EAF0' }};">

**`resources/views/emails/partials/lien-brut.blade.php`** — 1

- L12 · *texte* · Si le bouton ne s'affiche pas, copiez ce lien dans votre navigateur :

### app/Console/Commands


**`app/Console/Commands/MailAudit.php`** — 12

- L70 · *commande* · Adresse invalide : 
- L78 · *commande* · Transport « log » : rien ne partira réellement.
- L79 · *commande* · Corriger MAIL_MAILER dans .env puis : php artisan config:clear
- L123 · *commande* · Bonjour,\n\nJe souhaite commander cinquante cartes imprimées pour mon cabinet.
- L245 · *commande* · Aucun destinataire pour les alertes d\
- L246 · *commande* · Renseigner ADMIN_ALERT_RECIPIENTS, ou créer un compte administrateur.
- L251 · *commande* · Envoi réel en cours — une quinzaine de messages vont partir.
- L260 · *commande* · {$echecs} e-mail(s) sur {$total} n
- L261 · *commande* · Le détail figure ci-dessus, et la trace complète dans : php artisan mail:history
- L267 · *commande* · Les {$total} e-mails se fabriquent correctement. Relancer sans --dry-run pour éprouver le 
- L272 · *commande* · Les {$total} e-mails ont été acceptés par le fournisseur.
- L276 · *commande* · le serveur du destinataire. Vérifiez la boîte, et les indésirables.

**`app/Console/Commands/AutomationToken.php`** — 11

- L36 · *commande* · AUTOMATION_SCHEDULE_TOKEN=
- L37 · *commande* · MAKE_WEBHOOK_SECRET=
- L41 · *commande* ·   Méthode : POST
- L43 · *commande* ·   En-tête : X-Automation-Token: 
- L44 · *commande* ·   Cadence : toutes les minutes
- L48 · *commande* ·   1. Tant que AUTOMATION_SCHEDULE_TOKEN est vide, la route rend 404.
- L51 · *commande* ·   2. Le jeton passe en EN-TÊTE, pas dans l\
- L52 · *commande* ·      se retrouve dans les journaux et l\
- L53 · *commande* ·   3. MAKE_WEBHOOK_SECRET sert au sens INVERSE — il signe ce que nous
- L54 · *commande* ·      envoyons à Make, pour que le scénario rejette les faux prospects.
- L59 · *commande* · Ces valeurs ne sont pas enregistrées : copiez-les vous-même.

**`app/Console/Commands/GoogleCheck.php`** — 11

- L47 · *commande* · Console Google Cloud → Clients → votre ID client OAuth
- L64 · *commande* ·   2. UNE BARRE OBLIQUE EN TROP à la fin. Google compare littéralement.
- L65 · *commande* ·   3. LE STATUT « TEST » dans le menu Audience : seuls les comptes
- L66 · *commande* ·      ajoutés comme testeurs peuvent se connecter, les autres voient
- L67 · *commande* ·      « Accès bloqué ».
- L70 · *commande* · « Origines JavaScript autorisées » reste VIDE : ce champ ne sert qu\
- L71 · *commande* · connexions faites depuis le navigateur, jamais depuis un serveur.
- L75 · *commande* · GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET sont vides : le bouton reste masqué.
- L76 · *commande* · Les renseigner dans .env, puis : php artisan config:clear
- L81 · *commande* · Les clés sont en place. Si Google refuse encore, comparez son
- L82 · *commande* · « détails de l\

**`app/Console/Commands/MailTest.php`** — 10

- L45 · *commande* · ATTENTION : transport « log ». Rien ne partira réellement.
- L46 · *commande* · Corriger MAIL_MAILER dans .env puis : php artisan config:clear
- L52 · *commande* · RESEND_API_KEY est vide : aucun envoi n\
- L53 · *commande* · Renseigner la clé dans .env puis : php artisan config:clear
- L59 · *commande* · Envoi synchrone en cours (aucune file, aucune exception avalée)...
- L69 · *commande* · Test envoi — 
- L76 · *commande* · Transport OK — message accepté en {$ms} ms.
- L80 · *commande* · le serveur du destinataire (boîte de réception, spam, ou rejet).
- L85 · *commande* · du titulaire du compte. Tout autre destinataire sera refusé,
- L90 · *commande* · Journal des envois : php artisan mail:history

**`app/Console/Commands/UserDiagnose.php`** — 8

- L27 · *commande* · Aucun compte pour cette adresse.
- L32 · *commande* · En revanche, une inscription est EN ATTENTE de confirmation.
- L33 · *commande* ·   Créée le  : 
- L34 · *commande* ·   Expire le : 
- L36 · *commande* · → Confirmer sans e-mail : php artisan dev:confirm 
- L84 · *commande* · Aucun blocage côté compte. Si la connexion échoue, le mot de passe saisi 
- L91 · *commande* · Blocages identifiés :
- L96 · *commande* · → Tout corriger d\

**`app/Console/Commands/Dev/DevConfirm.php`** — 6

- L29 · *commande* · Aucune demande en attente pour {$email}.
- L30 · *commande* · Inscris-toi d\
- L36 · *commande* · Cette demande était EXPIRÉE (
- L37 · *commande* · Elle est réarmée ci-dessous : le lien fonctionnera.
- L60 · *commande* · Demande trouvée pour 
- L61 · *commande* · URL de confirmation (jeton régénéré, valable 

**`app/Console/Commands/MailHistory.php`** — 5

- L31 · *commande* · Aucun envoi enregistré.
- L32 · *commande* · Si des e-mails ont été déclenchés, vérifier que la table mail_logs est migrée.
- L52 · *commande* · Envoyés : <info>{$sent}</info> · Échecs : 
- L54 · *commande* · « envoyé » signifie : accepté par le serveur SMTP.
- L55 · *commande* · La livraison finale (boîte de réception ou spam) dépend du destinataire.

**`app/Console/Commands/QueueHealth.php`** — 5

- L41 · *commande* · {$stale} job(s) en attente depuis plus de 2 minutes : le worker est probablement ARRÊTÉ.
- L42 · *commande* ·   → Lancer : php artisan queue:work database --queue=mail,default
- L47 · *commande* · File « mail » engorgée : {$mail} > {$max}.
- L52 · *commande* · {$failed} job(s) en échec définitif.
- L53 · *commande* ·   → Inspecter : php artisan queue:failed   puis   php artisan queue:retry all

**`app/Console/Commands/Sauvegarder.php`** — 5

- L48 · *commande* · Seul MySQL est pris en charge par cette commande.
- L55 · *commande* · Sauvegarde en cours…
- L78 · *commande* · mysqldump a échoué : 
- L89 · *commande* · Le dump est anormalement petit (
- L124 · *commande* ·   retirée : {$fichier}

**`app/Console/Commands/VerifierTraductions.php`** — 5

- L80 · *commande* ·   {$ecarts} écart(s). Les fichiers de langue ne concordent pas.
- L85 · *commande* ·   Aucun écart : toutes les langues portent les mêmes clés.
- L100 · *commande* ·   Dossier absent : lang/{$code}
- L224 · *commande* ·   Pluriel incohérent — {$cle}
- L235 · *commande* ·   Paramètres différents — {$cle}

**`app/Console/Commands/Dev/DevMails.php`** — 5

- L25 · *commande* · Aucun fichier de log : 
- L34 · *commande* · Log vide.
- L52 · *commande* · Aucun lien trouvé dans les 
- L53 · *commande* · Vérifie que MAIL_MAILER=log et qu\
- L63 · *commande* · Liens trouvés (du plus ancien au plus récent) :

**`app/Console/Commands/AgregerStatistiques.php`** — 4

- L48 · *commande* · Agrégation sur {$jours} jour(s), en remontant depuis hier.
- L56 · *commande* ·   {$lignes} agrégat(s) écrit(s).
- L140 · *commande* · Purge des événements antérieurs au {$limite->toDateString()}.
- L153 · *commande* ·   {$total} événement(s) brut(s) supprimé(s).

**`app/Console/Commands/SendDailyReport.php`** — 4

- L62 · *commande* · Simulation — rien n\
- L76 · *commande* · DISCORD_WEBHOOK_URL n\
- L96 · *commande* · Le récapitulatif n\
- L102 · *commande* · Récapitulatif envoyé.

**`app/Console/Commands/UserRepair.php`** — 4

- L31 · *commande* · Aucun compte pour cette adresse.
- L64 · *commande* · Le mot de passe écrit ne se vérifie pas. Le cast « hashed » du modèle 
- L70 · *commande* · Compte réparé et vérifié.
- L80 · *commande* · Ce mot de passe s\

**`app/Console/Commands/Dev/DevUser.php`** — 4

- L38 · *commande* · Numéro invalide. Exemple : 77 383 13 64.
- L44 · *commande* · Un compte existe déjà pour {$email}.
- L45 · *commande* · Connexion : {$email} / (mot de passe existant)
- L68 · *commande* · Utilisateur créé et vérifié.

**`app/Console/Commands/NormalizePhones.php`** — 3

- L27 · *commande* · User #{$user->id} : numéro non normalisable « {$user->phone} » (laissé tel quel).
- L36 · *commande* · User #{$user->id} : « {$user->phone} » → « {$canonical} »
- L46 · *commande* · {$mode}Numéros à normaliser : {$changed} · non normalisables : {$invalid}

**`app/Console/Commands/ReconcilierPaiements.php`** — 3

- L66 · *commande* · Aucun paiement en attente depuis plus de {$jours} jour(s).
- L71 · *commande* · {$bloques->count()} paiement(s) en attente depuis plus de {$jours} jour(s) :
- L91 · *commande* ·   Total immobilisé : 

**`app/Console/Commands/SchemaCheck.php`** — 3

- L40 · *commande* · Modèle « {$only} » introuvable.
- L55 · *commande* · Schéma cohérent : aucun écart détecté.
- L60 · *commande* · {$this->problems} écart(s) détecté(s).

**`app/Console/Commands/Dev/DevReset.php`** — 3

- L28 · *commande* · Aucun compte pour {$email}.
- L29 · *commande* · Créer un compte de test : php artisan dev:user 
- L39 · *commande* · URL de réinitialisation pour 

**`app/Console/Commands/Sante.php`** — 2

- L72 · *commande* ·   {$alertes} point(s) d
- L77 · *commande* ·   Tout est au vert.

**`app/Console/Commands/NotifySubscriptions.php`** — 1

- L128 · *commande* ·   échu · 

**`app/Console/Commands/PurgeExpiredRegistrations.php`** — 1

- L23 · *commande* · Demandes en attente purgées : {$deleted}

**`app/Console/Commands/Dev/DevCommand.php`** — 1

- L19 · *commande* · Commande de développement : exécution refusée hors de APP_ENV=local.

### vues / (racine)


**`resources/views/design-system.blade.php`** — 44

- L3 · *attribut title* · Système de design
- L6 · *texte* · Système de design
- L7 · *texte* · Page locale. Voir
- L7 · *texte* · docs/DESIGN.md
- L7 · *texte* · pour les règles.
- L21 · *texte* · , calculé
- L22 · *texte* · . Le nom du ton désigne le
- L22 · *texte* · APP_NAME
- L27 · *texte* · Dans les deux tons,
- L28 · *texte* · : seul le carré change de teinte. Un monogramme
- L29 · *texte* · tantôt blanc sur vert, tantôt vert sur blanc, donnerait deux logos.
- L34 · *texte* · — sur fond clair
- L44 · *texte* · — sur fond sombre
- L55 · *texte* · Carte PVC
- L57 · *texte* · Ratio 1,586 (85,6 × 54 mm, ISO/IEC 7810 ID-1) · coins à angle vif ·
- L58 · *texte* · typographie en unités de conteneur · deux variantes, verte et blanche.
- L61 · *texte* · — le porteur : nom, code, fonction. Son QR mène
- L62 · *texte* · à sa carte.
- L63 · *texte* · — la plateforme, identique sur toutes les
- L64 · *texte* · cartes, sans aucune donnée de profil. Son QR mène à la plateforme.
- L67 · *texte* · Densité
- L67 · *texte* · — marges à 6 % de la largeur, QR à ≈47 % de
- L68 · *texte* · la hauteur, aucune zone morte de plus d'un quart de la hauteur.
- L73 · *texte* · Présentation « duo » — les deux faces en perspective, comme la référence.
- L95 · *texte* · Les quatre faces — deux variantes, recto et verso.
- L98 · *texte* · La variante
- L98 · *texte* · est conforme à ISO/IEC 18004
- L99 · *texte* · (code sombre sur fond clair). La
- L100 · *texte* · les lecteurs modernes la gèrent, d'autres non, et leur échec est
- L101 · *texte* · silencieux. À qualité d'impression égale, la blanche scanne plus sûrement.
- L120 · *texte* · — centré, symétrique. Son QR mène
- L121 · *texte* · à la carte du porteur.
- L128 · *texte* · — asymétrique, identique sur toutes
- L129 · *texte* · les cartes. Son QR mène à la
- L138 · *texte* · Trois tailles, avec permutation.
- L147 · *texte* · Composant téléphone
- L148 · *texte* · Ratio 9/19.5 · 280px, 240px, 220px selon le contexte.
- L153 · *texte* · Grande — 280px (hero)
- L158 · *texte* · Réduite — 240px (section sombre)
- L163 · *texte* · Sur socle, comme dans la section sombre :
- … et 4 autres

**`resources/views/welcome.blade.php`** — 1

- L4 · *texte* · config('app.name')])"

### app/Http/Requests


**`app/Http/Requests/Profile/WizardStepTwoRequest.php`** — 8

- L84 · *validation* · Votre téléphone est obligatoire.
- L85 · *validation* · Cette adresse e-mail n\
- L86 · *validation* · Cette adresse de site n\
- L87 · *validation* · Six réseaux sociaux au maximum.
- L88 · *validation* · Ce lien n\
- L89 · *validation* · Choisissez le réseau correspondant à ce lien.
- L90 · *validation* · Ce réseau n\
- L98 · *validation* · réseau

**`app/Http/Requests/Profile/WizardStepOneRequest.php`** — 6

- L45 · *validation* · Votre prénom est obligatoire.
- L46 · *validation* · Votre nom est obligatoire.
- L47 · *validation* · Votre fonction est obligatoire.
- L48 · *validation* · Ce fichier n\
- L49 · *validation* · Formats acceptés : JPG, PNG ou WEBP.
- L50 · *validation* · Votre image dépasse 2 Mo. Choisissez une image plus légère.

**`app/Http/Requests/Auth/RegisterRequest.php`** — 4

- L48 · *validation* · Le nom complet est obligatoire.
- L51 · *validation* · Le numéro de téléphone est obligatoire.
- L52 · *validation* · Le mot de passe est obligatoire.
- L53 · *validation* · Les deux mots de passe ne correspondent pas.

**`app/Http/Requests/Profile/CheckoutRequest.php`** — 4

- L43 · *validation* · Choisissez une formule.
- L44 · *validation* · Cette formule n\
- L45 · *validation* · Choisissez un moyen de paiement.
- L46 · *validation* · Ce moyen de paiement n\

**`app/Http/Requests/Profile/WizardStepThreeRequest.php`** — 4

- L47 · *validation* · Choisissez un modèle.
- L48 · *validation* · Ce modèle n\
- L49 · *validation* · Choisissez une variante de carte.
- L50 · *validation* · Cette variante de carte n\

**`app/Http/Requests/AccountUpdateRequest.php`** — 3

- L37 · *validation* · Votre nom est obligatoire.
- L38 · *validation* · Votre adresse e-mail est obligatoire.
- L39 · *validation* · Cette adresse e-mail est déjà utilisée.

**`app/Http/Requests/Admin/MotifRequest.php`** — 2

- L43 · *validation* · Un motif est obligatoire pour cette action.
- L44 · *validation* · Le motif doit être compréhensible par quelqu\

**`app/Http/Requests/Admin/PlanRequest.php`** — 2

- L72 · *validation* · périodicité
- L80 · *validation* · Cet identifiant technique est déjà pris par une autre formule.

**`app/Http/Requests/Admin/ExtendSubscriptionRequest.php`** — 1

- L36 · *validation* · Au-delà de 

**`app/Http/Requests/Profile/AdresseLivraisonRequest.php`** — 1

- L48 · *validation* · téléphone

### vues / components


**`resources/views/components/badge.blade.php`** — 6

- L8 · *php embarqué* · Expiré
- L9 · *php embarqué* · En attente
- L10 · *php embarqué* · Échoué
- L11 · *php embarqué* · Remboursé
- L12 · *php embarqué* · Publié
- L22 · *php embarqué* · badge text-bg-{$finalVariant}

**`resources/views/components/google-button.blade.php`** — 5

- L74 · *texte* · Connexion Google inactive.
- L75 · *texte* · GOOGLE_CLIENT_ID
- L75 · *texte* · GOOGLE_CLIENT_SECRET
- L76 · *texte* · php artisan config:clear
- L78 · *texte* · Ce repère n'apparaît qu'en développement.

**`resources/views/components/liste-resultats.blade.php`** — 3

- L42 · *texte* · après filtrage
- L45 · *texte* · Réinitialiser
- L58 · *texte* · Réinitialiser les filtres

**`resources/views/components/input.blade.php`** — 2

- L12 · *php embarqué* · {$fieldId}-error
- L12 · *php embarqué* · {$fieldId}-help

**`resources/views/components/pagination.blade.php`** — 2

- L17 · *texte* · résultat
- L24 · *texte* · résultat

**`resources/views/components/select.blade.php`** — 2

- L12 · *php embarqué* · {$fieldId}-error
- L12 · *php embarqué* · {$fieldId}-help

**`resources/views/components/textarea.blade.php`** — 2

- L12 · *php embarqué* · {$fieldId}-error
- L12 · *php embarqué* · {$fieldId}-help

**`resources/views/components/admin-action-form.blade.php`** — 1

- L63 · *attribut placeholder* · Ce motif sera lu dans le journal d'audit dans six mois. Écrivez une phrase.

**`resources/views/components/auth-field.blade.php`** — 1

- L8 · *php embarqué* · .str_replace([

**`resources/views/components/auth-password.blade.php`** — 1

- L19 · *php embarqué* · .str_replace([

**`resources/views/components/avatar-demo.blade.php`** — 1

- L51 · *attribut clip-path* · url(#avatar-cadre)

**`resources/views/components/breadcrumb.blade.php`** — 1

- L15 · *attribut aria-label* · Fil d'Ariane

**`resources/views/components/card-duo.blade.php`** — 1

- L34 · *texte* · Voir le verso

**`resources/views/components/card.blade.php`** — 1

- L72 · *texte* · Protocole d'identité numérique

**`resources/views/components/field.blade.php`** — 1

- L7 · *php embarqué* · .str_replace([

**`resources/views/components/password.blade.php`** — 1

- L45 · *attribut aria-label* · Afficher le mot de passe

**`resources/views/components/phone-field.blade.php`** — 1

- L97 · *attribut aria-label* · Indicatif du pays

### app/Http/Controllers


**`app/Http/Controllers/Admin/ClientBlockController.php`** — 4

- L34 · *flash / abort* · Le compte de {$user->name} est bloqué. Ses sessions ont été fermées.
- L45 · *flash / abort* · Le compte de {$user->name} est de nouveau actif.
- L51 · *flash / abort* · Vous ne pouvez pas bloquer votre propre compte.
- L55 · *flash / abort* · Un compte administrateur ne se bloque pas depuis cet écran.

**`app/Http/Controllers/LegalController.php`** — 3

- L41 · *flash / abort* · Conditions générales d\
- L135 · *flash / abort* · Politique de confidentialité
- L220 · *flash / abort* · Mentions légales

**`app/Http/Controllers/Admin/CardOrderController.php`** — 3

- L87 · *flash / abort* · Aucune commande sélectionnée n\
- L101 · *flash / abort* · Lot {$lot} créé avec {$eligibles->count()} carte(s).
- L129 · *flash / abort* · {$touchees} carte(s) mises à jour.

**`app/Http/Controllers/Admin/ProfileDeactivationController.php`** — 3

- L26 · *flash / abort* · Ce profil était déjà désactivé.
- L31 · *flash / abort* · Le profil « {$profile->full_name} » n
- L37 · *flash / abort* · Ce profil n

**`app/Http/Controllers/Auth/ConfirmRegistrationController.php`** — 2

- L83 · *flash / abort* · Ce lien a déjà été utilisé ou n\
- L107 · *flash / abort* · Votre compte est déjà confirmé. Connectez-vous.

**`app/Http/Controllers/NotificationController.php`** — 1

- L48 · *flash / abort* · Toutes vos notifications sont marquées comme lues.

**`app/Http/Controllers/Admin/PlanController.php`** — 1

- L39 · *flash / abort* · La formule « {$plan->name} » est créée.

**`app/Http/Controllers/Admin/TemplateController.php`** — 1

- L81 · *flash / abort* · « {$template->name} » est désormais le modèle proposé par défaut.

**`app/Http/Controllers/Profile/PaymentController.php`** — 1

- L42 · *flash / abort* · Créez d\

**`app/Http/Controllers/Profile/QrCodePageController.php`** — 1

- L26 · *flash / abort* · Créez d\

**`app/Http/Controllers/Profile/StatisticsController.php`** — 1

- L32 · *flash / abort* · Créez d\

### vues / profile


**`resources/views/profile/wizard/step-2.blade.php`** — 6

- L23 · *texte* · field('phone')" />
- L26 · *texte* · field('whatsapp')" />
- L33 · *texte* · field('maps_url')" />
- L38 · *texte* · field('public_email')" />
- L41 · *texte* · field('website')" />
- L44 · *texte* · field('address')" />

**`resources/views/profile/partials/qr-placeholder.blade.php`** — 4

- L39 · *texte* · Vos contacts scannent, votre profil s'ouvre.
- L41 · *texte* · Génération en cours de mise en place. Votre lien public fonctionne déjà.
- L48 · *texte* · Télécharger en PNG
- L49 · *texte* · Version SVG

**`resources/views/profile/wizard/step-1.blade.php`** — 4

- L15 · *texte* · field('first_name')" autofocus />
- L18 · *texte* · field('last_name')" />
- L23 · *texte* · field('job_title')" />
- L26 · *texte* · field('company')" />

**`resources/views/profile/printable.blade.php`** — 1

- L268 · *texte* · PROTOCOLE D'IDENTITÉ NUMÉRIQUE

**`resources/views/profile/wizard/step-3.blade.php`** — 1

- L124 · *texte* · fond() }};--pvc-encre:

### vues / layouts


**`resources/views/layouts/partials/admin-links.blade.php`** — 7

- L20 · *php embarqué* · admin.clients.*
- L21 · *php embarqué* · admin.profiles.*
- L22 · *php embarqué* · admin.payments.*
- L24 · *php embarqué* · admin.cards.*
- L27 · *php embarqué* · admin.templates.*
- L28 · *php embarqué* · admin.settings*
- L29 · *php embarqué* · admin.audit.*

**`resources/views/layouts/partials/notifications-menu.blade.php`** — 1

- L44 · *texte* · isUnread()) is-unread

**`resources/views/layouts/partials/sidebar-links.blade.php`** — 1

- L6 · *php embarqué* · profile.create.*

### vues / landing


**`resources/views/landing/sections/contact.blade.php`** — 3

- L103 · *texte* · user()?->name)"
- L110 · *texte* · user()?->email)"
- L122 · *texte* · user()?->phone)"

**`resources/views/landing/sections/trades.blade.php`** — 2

- L10 · *texte* · index, [1, 3]) ? ' trades__item--strong' : '' }}">
- L17 · *texte* · index, [1, 3]) ? ' trades__item--strong' : '' }}"

**`resources/views/landing/partials/phone-card.blade.php`** — 1

- L15 · *texte* · photo_path) }}"

### vues / public


**`resources/views/public/demo.blade.php`** — 5

- L47 · *texte* · Envoyer un e-mail
- L64 · *attribut title* · Aucun profil de démonstration
- L65 · *attribut message* · Créez le vôtre en moins de trois minutes.
- L70 · *texte* · Créer un compte
- L72 · *texte* · Retour à l'accueil

**`resources/views/public/profile.blade.php`** — 1

- L21 · *texte* · full_name"

### app/Services/RapportQuotidien.php


**`app/Services/RapportQuotidien.php`** — 5

- L85 · *service* · Nouveaux comptes
- L91 · *service* · Cartes créées
- L97 · *service* · Cartes mises en ligne
- L103 · *service* · Paiements encaissés
- L115 · *service* · Messages reçus

### vues / auth


**`resources/views/auth/login.blade.php`** — 1

- L4 · *texte* · config('app.name')])"

**`resources/views/auth/register.blade.php`** — 1

- L10 · *texte* · config('app.name')])"

**`resources/views/auth/reset-password.blade.php`** — 1

- L22 · *texte* · route('token') }}">

**`resources/views/auth/registration/pending.blade.php`** — 1

- L125 · *texte* · Développement uniquement

### vues / design-system


**`resources/views/design-system/cartes-publiques.blade.php`** — 4

- L18 · *texte* · Carte publique
- L21 · *texte* · Le composant tel qu'il est servi après un scan, à 375px. Le profil est
- L22 · *texte* · rendu sans photo ni bannière : ce sont les replis qu'il faut pouvoir
- L23 · *texte* · juger, puisque ce sont eux que verra tout client qui n'a rien téléversé.

### app/Services/NotificationService.php


**`app/Services/NotificationService.php`** — 3

- L30 · *service* · Paiement confirmé
- L60 · *service* · Votre carte a été consultée
- L72 · *service* · Un contact vous a enregistré

### app/Services/Admin


**`app/Services/Admin/AdminStatsService.php`** — 2

- L299 · *service* · Voir les paiements
- L312 · *service* · Voir les clients

**`app/Services/Admin/SubscriptionExtensionService.php`** — 1

- L35 · *service* · Ce client n

### vues / legal


**`resources/views/legal/page.blade.php`** — 2

- L8 · *texte* · Informations légales
- L10 · *texte* · Dernière mise à jour :

### vues / vendor


**`resources/views/vendor/pagination/qrid.blade.php`** — 2

- L32 · *texte* · Précédent
- L39 · *texte* · Précédent

### app/Models/User.php


**`app/Models/User.php`** — 2

- L321 · *modèle* · Réinitialisation du mot de passe
- L329 · *modèle* · Envoi du lien de réinitialisation impossible

### vues / account


**`resources/views/account/partials/update-password-form.blade.php`** — 1

- L50 · *attribut help* · Au moins 8 caractères.

### vues / dashboard


**`resources/views/dashboard/partials/card-block.blade.php`** — 1

- L16 · *texte* · is_active ? 'published' : 'draft'" />

### vues / notifications


**`resources/views/notifications/index.blade.php`** — 1

- L20 · *texte* · isUnread()) is-unread

### vues / profil


**`resources/views/profil/index.blade.php`** — 1

- L28 · *texte* · is_active ? 'published' : 'draft'" />

### vues / statistiques


**`resources/views/statistiques/index.blade.php`** — 1

- L25 · *attribut onchange* · this.form.submit()

### app/Mail/BaseMailable.php


**`app/Mail/BaseMailable.php`** — 1

- L71 · *mailable* · Échec définitif d\

### app/Support/DestinatairesEquipe.php


**`app/Support/DestinatairesEquipe.php`** — 1

- L122 · *support* · Destinataires de démonstration écartés
