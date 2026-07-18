@checkCondition($this->model, $field, $this->form)
    <div class="w-full px-2 {{ $field['style']['class'] ?? '' }}">
        <livewire:aura::user-teams
            :user-id="$this->model->id"
            :wire:key="'user-teams-'.$this->model->id"
            />
    </div>
@endcheckCondition
