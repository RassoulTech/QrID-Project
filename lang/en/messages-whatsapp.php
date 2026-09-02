<?php

/*
|------------------------------------------------------------------------------
| WHATSAPP MESSAGES
|------------------------------------------------------------------------------
| One message per CONTEXT, never a single generic message reused everywhere.
| See lang/fr/whatsapp.php for the reasoning; the same rules apply here.
|
| These are drafts, not fixed formulas: WhatsApp pre-fills the input without
| sending, so the sender reads, adjusts, then sends. They should read like a
| sentence someone could have written themselves.
|
| They never carry data that is not already public. The text travels inside a
| URL, which the browser remembers.
*/

return [

    'partage' => [
        'carte' => "Hello, here is my digital business card:\n:nom\n:url",
        'qr' => "Hello, here is my professional QR code — scan it or open the link:\n:nom\n:url",
    ],

    'invitation' => [
        'confrere' => 'Hello, I use QrID for my digital business card and find it handy. If you are interested: :url',
    ],

    'contact' => [
        'titulaire' => 'Hello :nom, I am getting in touch after viewing your digital business card.',
    ],

];
