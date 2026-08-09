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
        'primary_color' => 'couleur',
        'socials' => 'réseaux sociaux',
        'bio' => 'présentation',
    ],
];
