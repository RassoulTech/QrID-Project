<?php

/*
|--------------------------------------------------------------------------
| Messages de validation
|--------------------------------------------------------------------------
| Seules les règles réellement utilisées dans le projet sont traduites, plus
| celles que Laravel déclenche de lui-même. Recopier les 90 règles du
| framework pour n'en utiliser que vingt n'aide personne à maintenir ce
| fichier.
*/

return [
    'accepted' => 'Vous devez accepter :attribute.',
    'after' => ':Attribute doit être une date postérieure au :date.',
    'array' => ':Attribute doit être une liste.',
    'before' => ':Attribute doit être une date antérieure au :date.',
    'boolean' => ':Attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation ne correspond pas.',
    'current_password' => 'Ce mot de passe est incorrect.',
    'date' => ':Attribute n\'est pas une date valide.',
    'email' => ':Attribute doit être une adresse e-mail valide.',
    'exists' => 'La valeur sélectionnée pour :attribute n\'existe pas.',
    'file' => ':Attribute doit être un fichier.',
    'image' => ':Attribute doit être une image.',
    'in' => 'La valeur sélectionnée pour :attribute n\'est pas autorisée.',
    'integer' => ':Attribute doit être un nombre entier.',
    'lowercase' => ':Attribute doit être en minuscules.',
    'max' => [
        'array' => ':Attribute ne peut pas contenir plus de :max éléments.',
        'file' => ':Attribute ne doit pas dépasser :max kilo-octets.',
        'numeric' => ':Attribute ne doit pas dépasser :max.',
        'string' => ':Attribute ne doit pas dépasser :max caractères.',
    ],
    'mimes' => ':Attribute doit être un fichier de type : :values.',
    'min' => [
        'array' => ':Attribute doit contenir au moins :min éléments.',
        'file' => ':Attribute doit peser au moins :min kilo-octets.',
        'numeric' => ':Attribute doit être au moins :min.',
        'string' => ':Attribute doit contenir au moins :min caractères.',
    ],
    'not_regex' => 'Le format de :attribute n\'est pas valide.',
    'numeric' => ':Attribute doit être un nombre.',
    'regex' => 'Le format de :attribute n\'est pas valide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_with' => 'Le champ :attribute est obligatoire dès que :values est renseigné.',
    'same' => ':Attribute et :other doivent être identiques.',
    'size' => [
        'file' => ':Attribute doit peser :size kilo-octets.',
        'numeric' => ':Attribute doit valoir :size.',
        'string' => ':Attribute doit contenir :size caractères.',
    ],
    'string' => ':Attribute doit être une chaîne de caractères.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'uploaded' => 'L\'envoi de :attribute a échoué : fichier trop lourd ou connexion interrompue.',
    'uppercase' => ':Attribute doit être en majuscules.',
    'url' => ':Attribute doit être une adresse web valide.',

    // Mot de passe (règle Password::defaults())
    'password' => [
        'letters' => 'Le mot de passe doit contenir au moins une lettre.',
        'mixed' => 'Le mot de passe doit contenir une majuscule et une minuscule.',
        'numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
        'symbols' => 'Le mot de passe doit contenir au moins un caractère spécial.',
        'uncompromised' => 'Ce mot de passe apparaît dans une fuite de données connue. Choisissez-en un autre.',
    ],

    /*
    | Noms lisibles des champs. Sans eux, l'utilisateur lit « Le champ
    | job_title est obligatoire ». Toute nouvelle colonne exposée dans un
    | formulaire doit être ajoutée ici.
    */
    'attributes' => [
        'name' => 'nom complet',
        'first_name' => 'prénom',
        'last_name' => 'nom',
        'job_title' => 'fonction',
        'company' => 'entreprise',
        'email' => 'adresse e-mail',
        'public_email' => 'e-mail public',
        'phone' => 'téléphone',
        'whatsapp' => 'WhatsApp',
        'website' => 'site web',
        'address' => 'adresse',
        'photo' => 'photo',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'current_password' => 'mot de passe actuel',
        'template_id' => 'modèle',
        'primary_color' => 'variante de carte',
        'socials' => 'réseaux sociaux',
        'bio' => 'présentation',
    ],

    /*
     | NOS MESSAGES, PAR CONTEXTE.
     |
     | Le groupe `custom` de Laravel indexe par champ ; il ne peut pas
     | porter deux formulations differentes pour un meme champ. Ces cles
     | sont donc appelees explicitement depuis chaque FormRequest.
     */
    'messages' => [
        'compte' => [
            'nom_requis' => 'Votre nom est obligatoire.',
            'email_requis' => 'Votre adresse e-mail est obligatoire.',
            'email_pris' => 'Cette adresse e-mail est déjà utilisée.',
        ],
        'inscription' => [
            'nom_requis' => 'Le nom complet est obligatoire.',
            'email_requis' => 'L\'adresse e-mail est obligatoire.',
            'email_invalide' => 'L\'adresse e-mail n\'est pas valide.',
            'telephone_requis' => 'Le numéro de téléphone est obligatoire.',
            'mot_de_passe_requis' => 'Le mot de passe est obligatoire.',
            'mots_de_passe_differents' => 'Les deux mots de passe ne correspondent pas.',
        ],
        'motif' => [
            'requis' => 'Un motif est obligatoire pour cette action.',
            'trop_court' => 'Le motif doit être compréhensible par quelqu\'un qui relira le journal dans six mois — 10 caractères au minimum.',
        ],
        'prolongation' => [
            'trop_long' => 'Au-delà de :max jours, passez par une formule : elle laisse une trace comptable.',
        ],
        'formule' => [
            'slug_pris' => 'Cet identifiant technique est déjà pris par une autre formule.',
            'slug_invalide' => 'L\'identifiant technique n\'accepte que lettres, chiffres, tirets et tirets bas.',
        ],
        'paiement' => [
            'formule_requise' => 'Choisissez une formule.',
            'formule_absente' => 'Cette formule n\'est pas disponible.',
            'moyen_requis' => 'Choisissez un moyen de paiement.',
            'moyen_absent' => 'Ce moyen de paiement n\'est pas proposé.',
        ],
        'carte' => [
            'prenom_requis' => 'Votre prénom est obligatoire.',
            'nom_requis' => 'Votre nom est obligatoire.',
            'fonction_requise' => 'Votre fonction est obligatoire.',
            'image_invalide' => 'Ce fichier n\'est pas une image.',
            'image_formats' => 'Formats acceptés : JPG, PNG ou WEBP.',
            'image_trop_lourde' => 'Votre image dépasse 2 Mo. Choisissez une image plus légère.',
            'image_envoi_echoue' => 'L\'envoi a échoué : l\'image dépasse 2 Mo ou la connexion s\'est interrompue.',
            'telephone_requis' => 'Votre téléphone est obligatoire.',
            'email_invalide' => 'Cette adresse e-mail n\'est pas valide.',
            'site_invalide' => 'Cette adresse de site n\'est pas valide.',
            'reseaux_max' => 'Six réseaux sociaux au maximum.',
            'lien_invalide' => 'Ce lien n\'est pas une adresse valide.',
            'reseau_requis' => 'Choisissez le réseau correspondant à ce lien.',
            'reseau_absent' => 'Ce réseau n\'est pas proposé.',
            'modele_requis' => 'Choisissez un modèle.',
            'modele_absent' => 'Ce modèle n\'est plus disponible.',
            'variante_requise' => 'Choisissez une variante de carte.',
            'variante_absente' => 'Cette variante de carte n\'existe pas.',
        ],
    ],

    /*
     | LES NOMS DE CHAMPS, tels qu'ils apparaissent DANS une phrase.
     | Laravel les insere dans « Le champ :attribute est obligatoire »,
     | donc en minuscules et sans article.
     */
    'attributs' => [
        'nombre_jours' => 'nombre de jours',
        'motif' => 'motif',
        'nom_plan' => 'nom du plan',
        'identifiant_technique' => 'identifiant technique',
        'prix' => 'prix',
        'periodicite' => 'périodicité',
        'inclusion' => 'inclusion',
        'destinataire' => 'nom du destinataire',
        'telephone' => 'téléphone',
        'adresse' => 'adresse',
        'ville' => 'ville',
        'lien' => 'lien',
        'reseau' => 'réseau',
    ],

];
