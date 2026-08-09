<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotCandidateValidator;
use Aura\Base\Livewire\ComponentSlots\InvalidComponentSlotCandidate;
use Illuminate\Contracts\Routing\UrlRoutable;
use Livewire\Attributes\On;
use Livewire\Component;

class ValidGlobalSearchSlot extends Component
{
    public function render(): string
    {
        return '<div>Search</div>';
    }
}

interface SlotDependency {}

class ConcreteSlotDependency implements SlotDependency {}

class RouteBoundSlotDependency implements UrlRoutable
{
    public function getRouteKey(): string
    {
        return 'route-key';
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function resolveChildRouteBinding($childType, $value, $field): null
    {
        return null;
    }

    public function resolveRouteBinding($value, $field = null): null
    {
        return null;
    }
}

enum SlotMode
{
    case Default;
}

abstract class AbstractGlobalSearchSlot extends Component {}

class RequiredConstructorGlobalSearchSlot extends Component
{
    public function __construct(string $required) {}
}

class OptionalDependencyGlobalSearchSlot extends Component
{
    public function mount(ConcreteSlotDependency $dependency, string $optional = 'default'): void {}
}

class BoundInterfaceGlobalSearchSlot extends Component
{
    public function mount(SlotDependency $dependency): void {}
}

class RequiredScalarGlobalSearchSlot extends Component
{
    public function mount(string $query): void {}
}

class RequiredEnumGlobalSearchSlot extends Component
{
    public function mount(SlotMode $mode): void {}
}

class RequiredRouteBindingGlobalSearchSlot extends Component
{
    public function mount(RouteBoundSlotDependency $routeBound): void {}
}

class RequiredUnionGlobalSearchSlot extends Component
{
    public function mount(ConcreteSlotDependency|stdClass $dependency): void {}
}

class ValidPropertyMediaManagerSlot extends Component
{
    public array $modalAttributes = [];

    public string $model = '';

    public string $ownerToken = '';

    public ?array $selected = null;

    public string $slug = '';

    #[On('aura-media-selection-acknowledged')]
    public function acknowledgeMediaSelection(string $ownerToken, string $requestToken, string $outcome, ?string $errorCode = null): void {}

    public function expireMediaSelection(string $requestToken): void {}

    public static function modalClasses(): string
    {
        return 'max-w-7xl';
    }

    public function requestMediaSelection(array $value): void {}
}

class ValidMountMediaManagerSlot extends Component
{
    #[On('aura-media-selection-acknowledged')]
    public function acknowledgeMediaSelection(string $ownerToken, string $requestToken, string $outcome, ?string $errorCode = null): void {}

    public function expireMediaSelection(string $requestToken): void {}

    public function mount(
        string $model,
        string $slug,
        ?array $selected,
        string $ownerToken,
        iterable $modalAttributes,
        ConcreteSlotDependency $dependency,
    ): void {}

    public function requestMediaSelection(array $value): void {}
}

class IncompatibleSelectedMediaManagerSlot extends Component
{
    public array $modalAttributes = [];

    public string $model = '';

    public string $ownerToken = '';

    public array $selected = [];

    public string $slug = '';

    #[On('aura-media-selection-acknowledged')]
    public function acknowledgeMediaSelection(string $ownerToken, string $requestToken, string $outcome, ?string $errorCode = null): void {}

    public function expireMediaSelection(string $requestToken): void {}

    public function requestMediaSelection(array $value): void {}
}

class StaticOwnerTokenMediaManagerSlot extends Component
{
    public array $modalAttributes = [];

    public string $model = '';

    public static string $ownerToken = '';

    public ?array $selected = null;

    public string $slug = '';

    #[On('aura-media-selection-acknowledged')]
    public function acknowledgeMediaSelection(string $ownerToken, string $requestToken, string $outcome, ?string $errorCode = null): void {}

    public function expireMediaSelection(string $requestToken): void {}

    public function requestMediaSelection(array $value): void {}
}

class MissingListenerMediaManagerSlot extends Component
{
    public array $modalAttributes = [];

    public string $model = '';

    public string $ownerToken = '';

    public ?array $selected = null;

    public string $slug = '';

    public function acknowledgeMediaSelection(string $ownerToken, string $requestToken, string $outcome, ?string $errorCode = null): void {}

    public function expireMediaSelection(string $requestToken): void {}

    public function requestMediaSelection(array $value): void {}
}

class InvalidActionMediaManagerSlot extends MissingListenerMediaManagerSlot
{
    #[On('aura-media-selection-acknowledged')]
    public function acknowledgeMediaSelection(string $requestToken, string $ownerToken, string $outcome, ?string $errorCode = null): void {}

    public function requestMediaSelection(array $values): void {}
}

class EmptyModalClassesMediaManagerSlot extends ValidPropertyMediaManagerSlot
{
    public static function modalClasses(): string
    {
        return '';
    }
}

class ThrowingModalClassesMediaManagerSlot extends ValidPropertyMediaManagerSlot
{
    public static function modalClasses(): string
    {
        throw new RuntimeException('must not escape');
    }
}

test('a global search slot accepts an instantiable Livewire component', function () {
    $validator = new ComponentSlotCandidateValidator(app());

    expect($validator->validate('global-search', 'vendor/package', ValidGlobalSearchSlot::class))
        ->toBe(ValidGlobalSearchSlot::class);
});

test('candidate class names are canonical and may have one leading slash', function () {
    $validator = new ComponentSlotCandidateValidator(app());

    expect($validator->validate('global-search', 'vendor/package', '\\'.ValidGlobalSearchSlot::class))
        ->toBe(ValidGlobalSearchSlot::class);

    expect(fn () => $validator->validate('global-search', 'vendor/package', strtolower(ValidGlobalSearchSlot::class)))
        ->toThrow(InvalidComponentSlotCandidate::class, 'canonical');
});

test('global search rejects invalid classes and caller supplied mount data', function (mixed $candidate, string $requirement) {
    $validator = new ComponentSlotCandidateValidator(app());

    expect(fn () => $validator->validate('global-search', 'vendor/package', $candidate))
        ->toThrow(InvalidComponentSlotCandidate::class, $requirement);
})->with([
    'non string' => [new stdClass, 'class string'],
    'missing class' => ['Missing\\SearchComponent', 'existing class'],
    'not a component' => [stdClass::class, 'Livewire'],
    'abstract component' => [AbstractGlobalSearchSlot::class, 'instantiable'],
    'required constructor' => [RequiredConstructorGlobalSearchSlot::class, 'constructor'],
    'required scalar mount input' => [RequiredScalarGlobalSearchSlot::class, 'query'],
    'required enum mount input' => [RequiredEnumGlobalSearchSlot::class, 'mode'],
    'route binding mount input' => [RequiredRouteBindingGlobalSearchSlot::class, 'routeBound'],
    'union dependency mount input' => [RequiredUnionGlobalSearchSlot::class, 'dependency'],
]);

test('global search accepts resolvable concrete and bound interface mount dependencies', function () {
    app()->bind(SlotDependency::class, ConcreteSlotDependency::class);
    $validator = new ComponentSlotCandidateValidator(app());

    expect($validator->validate('global-search', 'vendor/package', OptionalDependencyGlobalSearchSlot::class))
        ->toBe(OptionalDependencyGlobalSearchSlot::class)
        ->and($validator->validate('global-search', 'vendor/package', BoundInterfaceGlobalSearchSlot::class))
        ->toBe(BoundInterfaceGlobalSearchSlot::class);
});

test('global search rejects unresolved and incorrectly bound interfaces', function () {
    $validator = new ComponentSlotCandidateValidator(app());

    expect(fn () => $validator->validate('global-search', 'vendor/package', BoundInterfaceGlobalSearchSlot::class))
        ->toThrow(InvalidComponentSlotCandidate::class, SlotDependency::class);

    app()->bind(SlotDependency::class, fn () => new stdClass);

    expect(fn () => $validator->validate('global-search', 'vendor/package', BoundInterfaceGlobalSearchSlot::class))
        ->toThrow(InvalidComponentSlotCandidate::class, SlotDependency::class);
});

test('media manager accepts property and mount based contract inputs', function () {
    $validator = new ComponentSlotCandidateValidator(app());

    expect($validator->validate('media-manager', 'vendor/package', ValidPropertyMediaManagerSlot::class))
        ->toBe(ValidPropertyMediaManagerSlot::class)
        ->and($validator->validate('media-manager', 'vendor/package', ValidMountMediaManagerSlot::class))
        ->toBe(ValidMountMediaManagerSlot::class);
});

test('media manager rejects incompatible inputs actions listeners and modal classes', function (string $candidate, string $requirement) {
    $validator = new ComponentSlotCandidateValidator(app());

    expect(fn () => $validator->validate('media-manager', 'vendor/package', $candidate))
        ->toThrow(InvalidComponentSlotCandidate::class, $requirement);
})->with([
    'selected must accept null' => [IncompatibleSelectedMediaManagerSlot::class, 'selected'],
    'properties must be writable' => [StaticOwnerTokenMediaManagerSlot::class, 'ownerToken'],
    'acknowledgement listener required' => [MissingListenerMediaManagerSlot::class, 'aura-media-selection-acknowledged'],
    'action signatures are exact' => [InvalidActionMediaManagerSlot::class, 'requestMediaSelection'],
    'modal classes are non empty' => [EmptyModalClassesMediaManagerSlot::class, 'modalClasses'],
    'modal classes exceptions fail validation' => [ThrowingModalClassesMediaManagerSlot::class, 'modalClasses'],
]);
