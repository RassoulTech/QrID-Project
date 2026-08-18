# SIX ÉVOLUTIONS TRANSVERSES — PROMPT COMPLET

Six chantiers à traiter. Les parties 2 à 4 touchent toutes les pages du projet :
traite-les par composant réutilisable, jamais écran par écran.

---

## 1. NOUVELLE GRILLE TARIFAIRE ET CARTE OFFERTE

Prix unique, identique à chaque paiement : **3 500 FCFA par trimestre (90 jours)**.
La carte PVC est **offerte une seule fois, à la première activation payée**.
Le prix ne change jamais : le client ne doit à aucun moment avoir l'impression
d'avoir acheté la carte.

Message commercial exact, à reprendre partout :
« 3 500 FCFA les 3 mois — votre carte PVC offerte à l'activation. »

| Plan | Prix | Durée | Contenu |
|---|---|---|---|
| Essai gratuit | 0 FCFA | 14 jours | Profil, QR Code, page publique. Aucune carte. |
| Standard (recommandé) | 3 500 FCFA | 90 jours | Tout de l'essai, statistiques, carte PVC offerte à la première activation. |
| Entreprise | à définir | — | Propose deux ou trois options avec justification. N'implémente rien avant ma validation. |

Le plan annuel à 25 000 FCFA devient incohérent : calcule l'écart, propose un
ajustement ou sa suppression, attends ma réponse.

**Migration** : les abonnements en cours conservent leur date de fin. Aucune
modification rétroactive.
Répercute partout : landing, page tarifs, paiement, admin, e-mails, seeders.

### Règle de la carte offerte
Indicateur `physical_card_granted` sur le compte.
- Accordée une seule fois, au premier paiement encaissé et confirmé.
- Jamais pendant l'essai gratuit.
- Aucun renouvellement ne déclenche de nouvelle carte.
- Un remplacement est une commande payante traitée manuellement.

### Chaîne de commande — table `card_orders`
```
id, user_id, profile_id, status, recipient_name, phone, address_line,
city, region, delivery_notes, batch_id, produced_at, shipped_at,
delivered_at, timestamps
```
Statuts : `pending` → `in_batch` → `produced` → `shipped` → `delivered`, plus `cancelled`.

**Collecte de l'adresse** au moment du paiement, après le choix du moyen :
destinataire, téléphone, adresse, ville, région, indications. Enregistrée
après confirmation du paiement.

**Côté client** : bloc « Ma carte physique » sur le tableau de bord, statut en
clair, adresse corrigeable tant que le statut est `pending`, e-mail à chaque
changement de statut.

**Côté admin** : écran « Cartes à produire », sélection multiple, création de
lots, export CSV pour l'imprimeur, export des PDF du lot en archive, changement
de statut par lot, journalisation dans `admin_actions`.

**Production par lots** : seuil configurable (défaut 20 commandes), alerte
admin par e-mail et dans le récapitulatif Discord, qui inclut désormais le
nombre de cartes en attente et le plus ancien délai.

**Suivi économique** en admin : coût de carte paramétrable, revenu cumulé par
client, marge nette, et surtout **taux de renouvellement au deuxième trimestre** —
c'est ce chiffre qui dira si le modèle est rentable.

**Transparence** : délai de livraison réaliste annoncé à l'écran de paiement et
dans l'e-mail, en valeur de configuration.

---

## 2. SÉLECTEUR DE LANGUE — TOUTES LES PAGES

Même mécanique que la bascule de thème, présent sur **toutes** les pages,
publiques comme authentifiées.

- Français par défaut, anglais en second.
- Icône claire dans la barre supérieure, à côté de la bascule de thème,
  affichant la langue courante. Dans le pied de page pour les pages publiques.
- Préférence enregistrée en base pour un utilisateur connecté, en session pour
  un visiteur.
- Appliquée côté serveur dès le premier rendu, aucun clignotement.
- Fonctionne sans JavaScript, via un formulaire POST.
- Traductions complètes dans `lang/fr` et `lang/en` : interface, validation,
  messages d'erreur, e-mails, pages légales.
- **Aucun texte en dur dans une vue.** Fais l'audit complet et liste-moi ceux
  que tu trouves avant de les extraire.
- Les e-mails partent dans la langue du destinataire.
- La page publique d'un profil s'affiche dans la langue du **visiteur**, pas
  celle du propriétaire.

---

## 3. MESSAGES D'ERREUR — AFFICHAGE UNIFORME

- Composant Blade unique, réutilisé partout : texte rouge sur fond rouge très
  clair, icône d'alerte, bouton de fermeture manuelle.
- **Disparition automatique après 30 secondes maximum**, en fondu.
- Sans JavaScript, le message reste affiché : il ne doit jamais être masqué par
  défaut dans le HTML.
- Les erreurs de **validation de champ** restent attachées à leur champ, sous le
  champ concerné, et ne disparaissent pas : seuls les messages flash globaux
  sont temporaires.
- Même traitement en vert pour les messages de succès, même durée.
- Lisibilité vérifiée dans les deux thèmes.

---

## 4. CHAMPS TÉLÉPHONE — SÉLECTEUR D'INDICATIF PAYS

Remplace le préfixe fixe +221 par un **sélecteur de pays**, sur **tous** les
champs téléphone du projet : inscription, création de profil, édition, paiement,
adresse de livraison.

- Liste avec drapeau, nom du pays et indicatif.
- Sénégal sélectionné par défaut.
- Pays d'Afrique de l'Ouest en tête de liste, puis liste complète.
- Le numéro est stocké au format international complet, indicatif inclus.
- La validation s'adapte au pays choisi : longueur et préfixes valides.
  La règle sénégalaise actuelle est conservée.
- Formatage à l'affichage selon les conventions du pays.
- **Composant Blade unique**, réutilisé partout. Select natif, fonctionne sans
  JavaScript.
- Rendu uniforme avec les autres champs : même hauteur, même rayon, même
  bordure, flèche personnalisée, état de focus identique. Vérifie la lisibilité
  des options natives en thème sombre.

---

## 5. CHAMPS OBLIGATOIRES

- Astérisque rouge après le libellé de chaque champ obligatoire, sur **tous** les
  formulaires du projet.
- Mention « optionnel » en gris à côté des libellés facultatifs.
- Légende en tête de formulaire expliquant l'astérisque.
- L'astérisque est **porté par le composant de champ et déduit de la règle de
  validation**, jamais écrit à la main dans une vue.
- `aria-required` renseigné pour les lecteurs d'écran.

---

## 6. DESIGN DE LA CARTE — LE BLANC DOMINE

Inverse la dominante : le blanc devient la couleur principale, le vert passe en
accent.

**Recto** : fond blanc. Nom du porteur en vert foncé, très grand. QR Code en vert
foncé sur blanc. Fonction en gris ou vert atténué. Un liseré vert foncé sur un
bord comme signature de marque.

**Verso** : fond blanc dominant. Logo et nom de la plateforme en vert foncé.
QR Code de la plateforme en vert foncé. Le vert n'intervient que sur les
éléments graphiques et les textes, jamais en aplat plein.

Coins toujours à angle vif. Densité maintenue, marges mesurées.
**Vérifie que le QR Code vert sur blanc reste scannable** : teste sur deux
téléphones, à l'écran et après impression.

---

## VÉRIFICATION AVANT LIVRAISON

- [ ] Prix identique partout, aucun écran ne suggère que la carte est payante
- [ ] Un essai gratuit ne crée aucune commande de carte
- [ ] Un renouvellement ne crée pas de seconde carte
- [ ] Adresse collectée, modifiable tant que la commande est en attente
- [ ] Export imprimeur : CSV et PDF du lot
- [ ] Sélecteur de langue présent et fonctionnel sur **chaque** page
- [ ] Aucun texte non traduit, aucune clé brute affichée
- [ ] Message d'erreur déclenché, disparition vérifiée après 30 secondes
- [ ] Sélecteur d'indicatif testé avec trois pays, numéro stocké et réaffiché
- [ ] Astérisques cohérents avec les règles de validation sur tous les formulaires
- [ ] Carte à dominante blanche, QR Code scannable après impression
- [ ] Rendu vérifié à 375px et dans les deux thèmes
- [ ] Suite de tests au vert
