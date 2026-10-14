{{--
  x-badge — le CACHET : fond teinté + bordure 1px + rayon 6 + icône + libellé.

      <x-badge status="active" />                  Actif ✓ (succès)
      <x-badge status="pending" />                 En attente ◷ (alerte)
      <x-badge status="failed" />                  Échoué ✕ (danger)
      <x-badge variant="success">Réussi</x-badge>  libellé libre
      <x-badge variant="neutral" :icon="false">Brouillon</x-badge>

  LA COULEUR NE PORTE JAMAIS SEULE L'INFORMATION (signature 4) : l'icône et
  le libellé disent la même chose que la teinte, pour qui ne la distingue
  pas. Chaque texte est mesuré sur SON fond teinté, dans les deux thèmes
  (voir _tokens.scss § 4).

  Variantes : success · warning · danger · info · neutral (alias Bootstrap
  `secondary`) · gold (premium).
--}}
@props([
    'status' => null,
    'variant' => null,
    'icon' => true,
    'soft' => true,
])

@php
    $map = [
        'active'    => ['success', __('common.etats.actif')],
        'trial'     => ['info',    __('common.etats.essai')],
        'expired'   => ['danger',  __('common.etats.expire')],
        'pending'   => ['warning', __('common.etats.en_attente')],
        'failed'    => ['danger',  __('common.etats.echoue')],
        'refunded'  => ['neutral', __('common.etats.rembourse')],
        'published' => ['success', __('common.etats.publie')],
        'draft'     => ['neutral', __('common.etats.brouillon')],
        'suspended' => ['danger',  __('common.etats.suspendu')],
    ];

    [$varianteStatut, $libelleStatut] = $map[$status] ?? ['neutral', $status];
    $variante = $variant ?? $varianteStatut;

    // Les noms de Bootstrap restent acceptés.
    $nom = [
        'success' => 'succes', 'warning' => 'alerte', 'danger' => 'danger',
        'info' => 'info', 'neutral' => 'neutre', 'secondary' => 'neutre',
        'light' => 'neutre', 'primary' => 'succes', 'gold' => 'or',
    ][$variante] ?? 'neutre';

    $icone = [
        'succes' => 'check', 'alerte' => 'clock', 'danger' => 'x',
        'info' => 'info', 'neutre' => null, 'or' => 'shield-check',
    ][$nom];
@endphp

<span {{ $attributes->merge(['class' => 'badge badge--'.$nom]) }}>
    @if ($icon && $icone)<x-icone :name="$icone" />@endif
    {{ trim($slot) !== '' ? $slot : $libelleStatut }}
</span>
