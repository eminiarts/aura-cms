<?php

namespace Aura\Base\Livewire\Resource;

class CreateModal extends Create
{
    public function mount($slug = null, $params = [])
    {
        $this->inModal = true;
        $this->params = $params;
        
        ray('CreateModal mount called with:', ['slug' => $slug, 'params' => $params])->orange();

        parent::mount($slug);
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function render()
    {
        return view($this->model->createView());
    }
}
