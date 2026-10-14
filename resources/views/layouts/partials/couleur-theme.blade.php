{{--
  LA COULEUR DE LA BARRE DU NAVIGATEUR — elle suit le thème posé par le serveur.

  Sable en clair, nuit verte en sombre : la barre d'adresse se fond dans la
  page au lieu de poser un bandeau vert au-dessus d'un fond sable. Ce sont
  les valeurs de `--page` (voir _tokens.scss) — une balise <meta> ne sait pas
  lire une propriété CSS, d'où cette seule recopie.
--}}
<meta name="theme-color" content="{{ App\Support\Theme::estSombre() ? '#0C1713' : '#F5EFE4' }}">
