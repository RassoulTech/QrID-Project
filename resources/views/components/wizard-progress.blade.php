{{--
  x-wizard-progress — barre de progression en segments.

  <x-wizard-progress :step="2" :total="3" />

  Rendu serveur uniquement : aucun JavaScript, aucune animation au défilement.
--}}
@props(['step', 'total' => 3])

<div class="mb-4" role="group"
     aria-label="{{ __('profile.wizard.progression', ['n' => $step, 'total' => $total]) }}">
    <div class="d-flex gap-2 mb-2">
        @for ($i = 1; $i <= $total; $i++)
            <div class="flex-fill rounded-pill {{ $i <= $step ? 'bg-primary' : 'bg-body-secondary' }}"
                 style="height:.3rem;" aria-hidden="true"></div>
        @endfor
    </div>
    <p class="text-secondary small mb-0">{{ __('profile.wizard.etape_sur', ['n' => $step, 'total' => $total]) }}</p>
</div>
