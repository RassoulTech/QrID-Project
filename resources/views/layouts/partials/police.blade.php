{{--
  LE PRÉCHARGEMENT DE LA POLICE — un seul fichier, auto-hébergé.

  Sans lui, le navigateur ne découvre la police qu'APRÈS avoir téléchargé et
  analysé la feuille de style : un aller-retour de plus avant le premier mot
  dans sa vraie forme. Le préchargement lance les deux téléchargements en
  parallèle.

  `crossorigin` est OBLIGATOIRE même pour un fichier du même domaine : les
  polices sont toujours demandées en mode CORS. Sans l'attribut, le fichier
  préchargé n'est pas réutilisé et part une seconde fois.

  Inclus par les gabarits de l'application et de la vitrine. La carte
  publique /p/{slug} décide elle-même (lot 3) : elle s'ouvre en 3G après un
  scan, et 46,8 Ko peuvent y valoir plus qu'une police.
--}}
<link rel="preload" href="{{ asset('fonts/schibsted-grotesk-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
