<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),

            /*
             | DÉLAI D'ATTENTE SMTP — 10 secondes, et ce n'est pas un détail.
             |
             | À `null`, PHP retombe sur default_socket_timeout : SOIXANTE
             | SECONDES. En production, QUEUE_CONNECTION vaut `sync` : l'envoi
             | se fait donc DANS la requête HTTP. Un serveur SMTP lent à
             | répondre bloquait ainsi la page une minute entière avant même
             | d'échouer — l'utilisateur regarde un écran figé et recharge,
             | ce qui déclenche une seconde tentative.
             |
             | Dix secondes suffisent largement à Gmail depuis Francfort. Au
             | delà, quelque chose ne va pas et il vaut mieux le dire vite que
             | de faire patienter.
             */
            'timeout' => (int) env('MAIL_TIMEOUT', 10),

            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Identité de l'expéditeur — SOURCE UNIQUE DE VÉRITÉ
    |--------------------------------------------------------------------------
    |
    | Seul endroit du projet qui définit l'expéditeur. Aucun Mailable, aucune
    | vue et aucun contrôleur ne doit coder une adresse en dur ni appeler env().
    | Le jour de la bascule vers le domaine professionnel, seul le .env change.
    |
    | Note : avec Gmail SMTP, l'adresse DOIT être identique à MAIL_USERNAME —
    | Gmail réécrit toute autre adresse d'expédition. Le nom affiché, lui,
    | reste celui du produit.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@identitepro.sn'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'QrID')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerte de volume (informative — n'a AUCUN pouvoir de blocage)
    |--------------------------------------------------------------------------
    |
    | AUCUN filtrage de destinataire n'existe dans ce projet. Tout e-mail part
    | réellement, quel que soit le domaine visé.
    |
    | Ce seuil déclenche seulement une ligne d'avertissement dans les logs
    | lorsque plus de N e-mails sont envoyés dans la même heure en local
    | (repère pour le quota Gmail, ~500/jour). L'envoi n'est jamais annulé.
    |
    */

    'hourly_alert_threshold' => (int) env('MAIL_HOURLY_ALERT', 100),

];
