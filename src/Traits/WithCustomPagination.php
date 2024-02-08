<?php

namespace Aura\Base\Traits;

use Illuminate\Pagination\Paginator;
use Livewire\WithPagination;

trait WithCustomPagination
{
    use WithPagination;

    public function getPublicPropertiesDefinedBySubClass()
    {
        return tap(parent::getPublicPropertiesDefinedBySubClass(), function (&$props) {
            $props[$this->pageName()] = $this->{$this->pageName()};
        });
    }

    public function getQueryString()
    {
        return array_merge([$this->pageName() => ['except' => 1]], $this->queryString);
    }

    public function initializeWithPagination()
    {
        $this->{$this->pageName()} = $this->resolvePage();

        Paginator::currentPageResolver(function () {
            return $this->{$this->pageName()};
        });

        Paginator::defaultView($this->paginationView());
    }

    public function nextPage()
    {
        $this->setPage($this->{$this->pageName()} + 1);
    }

    public function pageName(): string
    {
        if (property_exists($this, 'pageName')) {
            if (! isset($this->{$this->pageName})) {
                $this->{$this->pageName} = 1;
            }

            return $this->pageName;
        } else {
            return 'page';
        }
    }

    public function previousPage()
    {
        $this->setPage($this->{$this->pageName()} - 1);
    }

    public function resolvePage()
    {
        return request()->query($this->pageName(), $this->{$this->pageName()});
    }

    public function setPage($page)
    {
        $this->{$this->pageName()} = $page;
    }
}
