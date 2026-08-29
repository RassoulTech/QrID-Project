{{--
  x-badge — statut court (abonnement, paiement, profil).

  <x-badge status="active" />                      libellé automatique
  <x-badge status="pending">En attente</x-badge>   libellé personnalisé
  <x-badge variant="success">Payé</x-badge>        variante directe

  Statuts reconnus : active, trial, expired, pending, failed, refunded,
                     published, draft, suspended
  Props : status (mappé sur une variante + un libellé), variant (surcharge),
          soft (fond pâle, défaut : true)
--}}
@props([
    'status' => null,
    'variant' => null,
    'soft' => true,
])

@php
    $map = [
        'active'    => ['success', __('common.etats.actif')],
        'trial'     => ['info',    __('common.etats.essai')],
        'expired'   => ['danger',  __('common.etats.expire')],
        'pending'   => ['warning', __('common.etats.en_attente')],
        'failed'    => ['danger',  __('common.etats.echoue')],
        'refunded'  => ['secondary', __('common.etats.rembourse')],
        'published' => ['success', __('common.etats.publie')],
        'draft'     => ['secondary', __('common.etats.brouillon')],
        'suspended' => ['danger',  __('common.etats.suspendu')],
    ];

    [$mappedVariant, $mappedLabel] = $map[$status] ?? ['secondary', $status];

    $finalVariant = $variant ?? $mappedVariant;
    $classes = $soft
        ? "badge text-bg-light text-{$finalVariant} border border-{$finalVariant}-subtle"
        : "badge text-bg-{$finalVariant}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ trim($slot) !== '' ? $slot : $mappedLabel }}
</span>
