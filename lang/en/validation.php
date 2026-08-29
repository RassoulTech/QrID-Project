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
     | OUR MESSAGES, BY CONTEXT.
     |
     | Laravel's `custom` group is indexed by field; it cannot carry two
     | different wordings for the same field. These keys are therefore
     | called explicitly from each FormRequest.
     */
    'messages' => [
        'compte' => [
            'nom_requis' => 'Your name is required.',
            'email_requis' => 'Your email address is required.',
            'email_pris' => 'This email address is already in use.',
        ],
        'inscription' => [
            'nom_requis' => 'Your full name is required.',
            'email_requis' => 'An email address is required.',
            'email_invalide' => 'This email address is not valid.',
            'telephone_requis' => 'A phone number is required.',
            'mot_de_passe_requis' => 'A password is required.',
            'mots_de_passe_differents' => 'The two passwords do not match.',
        ],
        'motif' => [
            'requis' => 'A reason is required for this action.',
            'trop_court' => 'The reason must make sense to whoever reads the log six months from now — 10 characters minimum.',
        ],
        'prolongation' => [
            'trop_long' => 'Beyond :max days, use a plan instead: it leaves an accounting trail.',
        ],
        'formule' => [
            'slug_pris' => 'This technical identifier is already taken by another plan.',
            'slug_invalide' => 'The technical identifier accepts only letters, digits, hyphens and underscores.',
        ],
        'paiement' => [
            'formule_requise' => 'Choose a plan.',
            'formule_absente' => 'This plan is not available.',
            'moyen_requis' => 'Choose a payment method.',
            'moyen_absent' => 'This payment method is not offered.',
        ],
        'carte' => [
            'prenom_requis' => 'Your first name is required.',
            'nom_requis' => 'Your last name is required.',
            'fonction_requise' => 'Your job title is required.',
            'image_invalide' => 'This file is not an image.',
            'image_formats' => 'Accepted formats: JPG, PNG or WEBP.',
            'image_trop_lourde' => 'Your image is over 2 MB. Choose a lighter one.',
            'image_envoi_echoue' => 'The upload failed: the image is over 2 MB, or the connection dropped.',
            'telephone_requis' => 'Your phone number is required.',
            'email_invalide' => 'This email address is not valid.',
            'site_invalide' => 'This website address is not valid.',
            'reseaux_max' => 'Six social networks at most.',
            'lien_invalide' => 'This link is not a valid address.',
            'reseau_requis' => 'Choose the network matching this link.',
            'reseau_absent' => 'This network is not offered.',
            'modele_requis' => 'Choose a template.',
            'modele_absent' => 'This template is no longer available.',
            'variante_requise' => 'Choose a card variant.',
            'variante_absente' => 'This card variant does not exist.',
        ],
    ],

    /*
     | FIELD NAMES, as they read INSIDE a sentence. Laravel drops them into
     | "The :attribute field is required", so: lowercase, no article.
     */
    'attributs' => [
        'nombre_jours' => 'number of days',
        'motif' => 'reason',
        'nom_plan' => 'plan name',
        'identifiant_technique' => 'technical identifier',
        'prix' => 'price',
        'periodicite' => 'billing period',
        'inclusion' => 'included item',
        'destinataire' => 'recipient name',
        'telephone' => 'phone number',
        'adresse' => 'address',
        'ville' => 'city',
        'lien' => 'link',
        'reseau' => 'network',
    ],

];
