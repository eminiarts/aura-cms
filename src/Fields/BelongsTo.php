<?php

namespace Eminiarts\Aura\Fields;

use App\Models\User;

class BelongsTo extends Field
{
    protected string $view = 'components.fields.belongsto';

    public string $component = 'fields.belongsto';

    public bool $group = true;

    public function queryFor($model)
    {
        return function ($query) use ($model) {
            return $query->where('user_id', $model->id);
        };
    }

    public function get($field, $value)
    {
        dd('get');

        return json_decode($value, true);
    }

    public function display($field, $value)
    {
        $model = User::find($value);

        return "<a class='font-bold text-sky-500' href='/User/".$model->id."/edit'>".$model->name.'</a>';
    }
}
