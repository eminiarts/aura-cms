<?php

namespace Eminiarts\Aura\Fields;

use Exception;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\View\Component;
use Illuminate\View\View;

class Field extends Component implements Htmlable
{
    use Macroable;
    use Tappable;

    public function get($field, $value)
    {
        return $value;
    }

    public function display($field, $value)
    {
        return $value;
    }

    public string $type = 'input';

    public bool $group = false;

    public $field;
    // public $component;

    public function field($field)
    {
        // $this->field = $field;
        $this->withAttributes($field);

        return $this;
    }

    public function value($value)
    {
        return $value;
    }

    protected string $view;

    // public function __construct($attr)
    // {
    //     dd('constr');
    // }

    public function view(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    public function toHtml(): string
    {
        return $this->render()->render();
    }

    public function getView(): string
    {
        if (! isset($this->view)) {
            throw new Exception('Class ['.static::class.'] extends ['.ViewComponent::class.'] but does not have a [$view] property defined.');
        }

        return $this->view;
    }

    public function render(): View
    {
        return view(
            $this->getView(),
            array_merge(
                $this->data(),
                isset($this->viewIdentifier) ? [$this->viewIdentifier => $this] : [],
            ),
        );
    }
}
