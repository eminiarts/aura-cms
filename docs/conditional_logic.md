# Conditional Logic

## View

In the View, each field is wrapped:

```html
@foreach($this->fields as $key => $field)
<x-fields.conditions :field="$field" :model="$model">
    <div wire:key="resource-field-{{ $key }}">
        <x-dynamic-component :component="$field['field']->component()" :field="$field" :form="$form" />
    </div>
</x-fields.conditions>
@endforeach
```

## Check Conditional Logic

`Aura::checkCondition($model, $field)`

1. The conditional logic is an array of conditions. Each condition has an operator, value, and field. The field can either be a custom field or a role.
2. If the field is a role, the value will be the role name.
3. If the field is a custom field, the value will be the value to compare the field to.
4. The operator can be any of the following: ==, !=, <=, >, <, >=
5. If the operator is not recognized, the field will not be shown.
6. If the field is a role, the condition will be satisfied if the user has the specified role.
7. If the field is a custom field, the condition will be satisfied if the value of the field is equal to the specified value.
8. If the operator is !=, the condition will be satisfied if the value of the field is not equal to the specified value.
9. If the operator is <=, the condition will be satisfied if the value of the field is less than or equal to the specified value.
10. If the operator is >, the condition will be satisfied if the value of the field is greater than the specified value.
11. If the operator is <, the condition will be satisfied if the value of the field is less than the specified value.
12. If the operator is >=, the condition will be satisfied if the value of the field is greater than or equal to the specified value.
13. If the user is a super admin, the condition will be satisfied.
14. If the model does not have the field specified in the condition, the condition will not be satisfied. 