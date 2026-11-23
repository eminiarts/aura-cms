<?php

namespace Aura\Base\Livewire;

use Aura\Base\Fields;
use Aura\Base\ThemeTokens;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The living style guide at admin/styleguide.
 *
 * URL parameters (all optional) make a render deterministic, e.g. for screenshots:
 * - mode=light|dark       render the style guide content in that color mode
 * - section={id}          render only that section (see sections())
 * - density, radius, sidebar  preview an appearance preset without saving it
 */
class Styleguide extends Component
{
    /**
     * Appearance setting => its presets, default first. Mirrors ThemeTokens::design().
     *
     * @var array<string, list<string>>
     */
    public const DESIGN_PRESETS = [
        'interface-density' => ['comfortable', 'compact'],
        'corner-radius' => ['rounded', 'subtle', 'square'],
        'sidebar-size' => ['standard', 'compact', 'dense'],
    ];

    /** Group of the design variables that no preset changes (the control height scale). */
    public const FIXED_GROUP = 'control-height';

    /** @var list<string> */
    public const MODES = ['light', 'dark'];

    #[Url(except: '')]
    public string $density = '';

    /**
     * Values for the field specimens. The real field views bind to form.fields.{slug},
     * as on a resource form.
     *
     * @var array{fields: array<string, mixed>}
     */
    public array $form = ['fields' => [
        'sg_title' => 'Getting started with Aura',
        'sg_slug' => 'getting-started-with-aura',
        'sg_email' => 'bajram@example.com',
        'sg_phone' => '+41 44 123 45 67',
        'sg_number' => 42,
        'sg_password' => '',
        'sg_textarea' => 'Aura reads its colours, spacing and corners from the theme.',
        'sg_select' => 'review',
        'sg_status' => 'published',
        'sg_radio' => 'team',
        'sg_checkbox' => ['news'],
        'sg_boolean' => true,
        'sg_date' => '23.11.2026',
        'sg_datetime' => '23.11.2026 09:30',
        'sg_time' => '09:30',
        'sg_color' => '#2563eb',
    ]];

    #[Url(except: '')]
    public string $mode = '';

    #[Url(except: '')]
    public string $radius = '';

    #[Url(except: '')]
    public string $section = '';

    #[Url(as: 'sidebar', except: '')]
    public string $sidebarSize = '';

    public bool $toggleOff = false;

    public bool $toggleOn = true;

    /**
     * Each appearance setting with the CSS variables it writes and their value per preset, plus
     * one fixed group (FIXED_GROUP) for the variables that are the same in every preset.
     *
     * @return array<string, array{label: string, default: string, fixed: bool, presets: array<string, array<string, string>>, variables: array<string, array{expert: string|null, usedBy: string}>}>
     */
    public function designTokens(): array
    {
        $labels = [
            'interface-density' => __('Interface density'),
            'corner-radius' => __('Corner radius'),
            'sidebar-size' => __('Sidebar size'),
        ];

        $expertSettings = array_flip(ThemeTokens::designOverrides());
        $usedBy = $this->tokenUsage();
        $groups = [];

        foreach (self::DESIGN_PRESETS as $setting => $presets) {
            $values = [];

            foreach ($presets as $preset) {
                $values[$preset] = ThemeTokens::design([$setting => $preset]);
            }

            // A setting owns the variables that change between its presets.
            $variables = array_values(array_filter(
                array_keys($values[$presets[0]]),
                fn (string $variable): bool => count(array_unique(array_column($values, $variable))) > 1,
            ));

            $groups[$setting] = [
                'label' => $labels[$setting],
                'default' => $presets[0],
                'fixed' => false,
                'presets' => array_map(
                    fn (array $tokens): array => array_intersect_key($tokens, array_flip($variables)),
                    $values,
                ),
                'variables' => collect($variables)->mapWithKeys(fn (string $variable): array => [$variable => [
                    'expert' => $expertSettings[$variable] ?? null,
                    'usedBy' => $usedBy[$variable] ?? '',
                ]])->all(),
            ];
        }

        $owned = collect($groups)->flatMap(fn (array $group): array => array_keys($group['variables']))->all();
        $fixed = array_diff_key(ThemeTokens::design([]), array_flip($owned));

        if ($fixed !== []) {
            $groups[self::FIXED_GROUP] = [
                'label' => __('Control height'),
                'default' => 'fixed',
                'fixed' => true,
                'presets' => ['fixed' => $fixed],
                'variables' => collect(array_keys($fixed))->mapWithKeys(fn (string $variable): array => [$variable => [
                    'expert' => $expertSettings[$variable] ?? null,
                    'usedBy' => $usedBy[$variable] ?? '',
                ]])->all(),
            ];
        }

        return $groups;
    }

    /**
     * Every field type, grouped, with the component that renders it on a form. Shown as
     * the field catalogue; the types with a live specimen are marked.
     *
     * @return array<string, array<string, string>>
     */
    public function fieldCatalogue(): array
    {
        return [
            __('Text') => ['Text' => 'input', 'Textarea' => 'input', 'Email' => 'input', 'Phone' => 'input', 'Number' => 'input', 'Password' => 'input', 'Slug' => 'input + lock', 'Wysiwyg' => 'editor', 'Code' => 'editor', 'Json' => 'editor'],
            __('Choice') => ['Select' => 'select', 'Status' => 'listbox + pill', 'Radio' => 'radios', 'Checkbox' => 'checkboxes', 'Boolean' => 'toggle', 'Tags' => 'chips', 'AdvancedSelect' => 'search + chips'],
            __('Date, time and colour') => ['Date' => 'date picker', 'Datetime' => 'date picker', 'Time' => 'input', 'Color' => 'input + swatch'],
            __('Relations') => ['BelongsTo' => 'search select', 'BelongsToMany' => 'table', 'HasMany' => 'table', 'HasOne' => 'placeholder (not built yet)', 'Roles' => 'select', 'UserTeams' => 'list', 'Permissions' => 'matrix'],
            __('Media') => ['Image' => 'media picker', 'File' => 'media picker', 'Embed' => 'input + preview'],
            __('Layout') => ['Panel' => 'card', 'Tabs' => 'tabs', 'Tab' => 'tab', 'Group' => 'sub-panel', 'Repeater' => 'repeated rows', 'Heading' => 'heading', 'HorizontalLine' => 'divider'],
            __('Special') => ['ID' => 'read-only', 'Hidden' => 'none', 'View' => 'custom view', 'ViewValue' => 'read-only', 'LivewireComponent' => 'component', 'GlobalAdmin' => 'toggle'],
        ];
    }

    /**
     * Live specimens of the resource fields, grouped by what they draw. Each one is
     * the real fields/* view a resource form renders, bound to $form.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function fieldSpecimens(): array
    {
        $groups = [
            __('Text') => [
                ['type' => Fields\Text::class, 'name' => 'Title', 'slug' => 'sg_title', 'validation' => 'required', 'style' => ['width' => 50]],
                ['type' => Fields\Slug::class, 'name' => 'Slug', 'slug' => 'sg_slug', 'based_on' => 'sg_title', 'style' => ['width' => 50]],
                ['type' => Fields\Email::class, 'name' => 'Email', 'slug' => 'sg_email', 'style' => ['width' => 50]],
                ['type' => Fields\Phone::class, 'name' => 'Phone', 'slug' => 'sg_phone', 'style' => ['width' => 50]],
                ['type' => Fields\Number::class, 'name' => 'Number', 'slug' => 'sg_number', 'instructions' => 'Help text sits in a tooltip next to the label.', 'style' => ['width' => 50]],
                ['type' => Fields\Password::class, 'name' => 'Password', 'slug' => 'sg_password', 'style' => ['width' => 50]],
                ['type' => Fields\Textarea::class, 'name' => 'Textarea', 'slug' => 'sg_textarea'],
            ],
            __('Choice') => [
                ['type' => Fields\Select::class, 'name' => 'Select', 'slug' => 'sg_select', 'options' => ['draft' => __('Draft'), 'review' => __('In review'), 'published' => __('Published')], 'style' => ['width' => 50]],
                ['type' => Fields\Status::class, 'name' => 'Status', 'slug' => 'sg_status', 'style' => ['width' => 50], 'options' => [
                    ['key' => 'draft', 'value' => __('Draft'), 'color' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
                    ['key' => 'published', 'value' => __('Published'), 'color' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400'],
                ]],
                ['type' => Fields\Radio::class, 'name' => 'Radio', 'slug' => 'sg_radio', 'style' => ['width' => 33], 'options' => [['key' => 'private', 'value' => __('Only me')], ['key' => 'team', 'value' => __('My team')]]],
                ['type' => Fields\Checkbox::class, 'name' => 'Checkbox', 'slug' => 'sg_checkbox', 'style' => ['width' => 33], 'options' => [['key' => 'news', 'value' => __('Newsletter')], ['key' => 'updates', 'value' => __('Product updates')]]],
                ['type' => Fields\Boolean::class, 'name' => 'Boolean', 'slug' => 'sg_boolean', 'style' => ['width' => 33]],
            ],
            __('Date, time and colour') => [
                ['type' => Fields\Date::class, 'name' => 'Date', 'slug' => 'sg_date', 'style' => ['width' => 25]],
                ['type' => Fields\Datetime::class, 'name' => 'Date and time', 'slug' => 'sg_datetime', 'style' => ['width' => 25]],
                ['type' => Fields\Time::class, 'name' => 'Time', 'slug' => 'sg_time', 'style' => ['width' => 25]],
                ['type' => Fields\Color::class, 'name' => 'Color', 'slug' => 'sg_color', 'style' => ['width' => 25]],
            ],
        ];

        foreach ($groups as $group => $fields) {
            foreach ($fields as $i => $field) {
                $groups[$group][$i]['field'] = app($field['type'])->field($field);
            }
        }

        return $groups;
    }

    public function mount(): void
    {
        $this->mode = in_array($this->mode, self::MODES, true) ? $this->mode : '';
        $this->section = array_key_exists($this->section, $this->sections()) ? $this->section : '';
        $this->density = in_array($this->density, self::DESIGN_PRESETS['interface-density'], true) ? $this->density : '';
        $this->radius = in_array($this->radius, self::DESIGN_PRESETS['corner-radius'], true) ? $this->radius : '';
        $this->sidebarSize = in_array($this->sidebarSize, self::DESIGN_PRESETS['sidebar-size'], true) ? $this->sidebarSize : '';
    }

    /**
     * CSS variables for the presets chosen through the URL, applied to the style guide only.
     *
     * @return array<string, string>
     */
    public function previewTokens(): array
    {
        $tokens = [];
        $selected = [
            'interface-density' => $this->density,
            'corner-radius' => $this->radius,
            'sidebar-size' => $this->sidebarSize,
        ];

        foreach ($this->designTokens() as $setting => $group) {
            if (isset($selected[$setting], $group['presets'][$selected[$setting]])) {
                $tokens = [...$tokens, ...$group['presets'][$selected[$setting]]];
            }
        }

        return $tokens;
    }

    public function render()
    {
        // Gives the error-state specimen a real validation message, read by fields/wrapper.
        $this->addError('form.fields.styleguide-slug', __('The slug may only contain letters, numbers and dashes.'));

        $sections = $this->sections();

        return view('aura::livewire.styleguide', [
            'sections' => $sections,
            'visibleSections' => $this->section === '' ? $sections : array_intersect_key($sections, [$this->section => true]),
            'previewTokens' => $this->previewTokens(),
        ])->layout('aura::components.layout.app');
    }

    /**
     * Section id => label and description. The order sets the numbering and the top navigation.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public function sections(): array
    {
        return [
            'tokens' => [
                'label' => __('Design tokens'),
                'description' => __('Appearance settings write CSS variables on the page root. Panels, buttons and the sidebar read them, so one setting restyles the admin. The values come from ThemeTokens::design().'),
            ],
            'colors' => [
                'label' => __('Colors'),
                'description' => __('Every color is a CSS variable set by the theme (config/aura.php and the Settings page, rendered by layout/colors.blade.php). The swatches show the live values.'),
            ],
            'typography' => [
                'label' => __('Typography'),
                'description' => __('One font family from the theme (--aura-font-sans). The admin uses text-2xs to text-3xl; recurring roles are listed below.'),
            ],
            'icons' => [
                'label' => __('Icons'),
                'description' => __('<x-aura::icon icon="…" size="xs|sm|base|lg" /> renders 16, 20, 24 or 32 px.'),
            ],
            'buttons' => [
                'label' => __('Buttons'),
                'description' => __('<x-aura::button> with variants as sub-components and sizes through the size prop. Height follows --aura-control-height-*, radius --aura-control-radius.'),
            ],
            'forms' => [
                'label' => __('Form controls'),
                'description' => __('Inputs from components/input/*, sized like buttons (xs, sm, base, lg) and rounded with --aura-control-radius. Resource fields wrap them with a label, help tooltip and validation error (fields/wrapper).'),
            ],
            'fields' => [
                'label' => __('Fields'),
                'description' => __('Resource fields (src/Fields) are what a resource form is built from. Each renders one of the form controls above inside fields/wrapper: label, optional help tooltip, the control, the error. The specimens below are the real field views; the catalogue lists every field type.'),
            ],
            'sizing' => [
                'label' => __('Control sizing'),
                'description' => __('Controls that sit next to each other share one height. Buttons, inputs and selects take one size prop; the height of each size comes from --aura-control-height-*, the corners from --aura-control-radius. Heights below are measured in the browser.'),
            ],
            'badges' => [
                'label' => __('Badges & pills'),
                'description' => __('Four pill shapes are in use: status pills (dot + ring), sidebar counters, trend pills and chips (selected values in Tags and AdvancedSelect).'),
            ],
            'cards' => [
                'label' => __('Cards & panels'),
                'description' => __('.aura-card is the base surface for panels and widgets. Its padding and radius come from the density and radius tokens.'),
            ],
            'charts' => [
                'label' => __('Charts'),
                'description' => __('The real dashboard widgets (components/widgets) with static demo data. Charts use ApexCharts and the primary palette.'),
            ],
            'table' => [
                'label' => __('Table'),
                'description' => __('The resource index table: toolbar, filter tabs, header, rows, selection, row actions, empty state and pagination.'),
            ],
            'tabs' => [
                'label' => __('Tabs'),
                'description' => __('Underline tabs split a resource form. Segmented tabs switch table filters and views.'),
            ],
            'navigation' => [
                'label' => __('Sidebar'),
                'description' => __('Real navigation.item and navigation.heading components in the three sidebar themes (sidebar-type). The first item is active.'),
            ],
            'menus' => [
                'label' => __('Dropdown menus'),
                'description' => __('<x-aura::dropdown> with <x-aura::dropdown-link> items. The open panel is also shown statically.'),
            ],
            'popovers' => [
                'label' => __('Popovers & tooltips'),
                'description' => __('Popovers are dropdowns that stay open for richer content (closeOnSelect=false). Tooltips come in two flavours: tippy and a CSS-only tooltip.'),
            ],
            'overlays' => [
                'label' => __('Modals & slide-overs'),
                'description' => __('Alpine dialog components. The buttons open the real overlays; the panel below is a static specimen.'),
            ],
            'notifications' => [
                'label' => __('Notifications'),
                'description' => __('Toasts are dispatched with the notify event and rendered by the notification component in the layout.'),
            ],
            'feedback' => [
                'label' => __('Feedback & loading'),
                'description' => __('Spinners, skeletons and inline alerts.'),
            ],
            'auth' => [
                'label' => __('Authentication'),
                'description' => __('The login form as rendered on /login. Register, password reset and 2FA use the same components.'),
            ],
        ];
    }

    /**
     * URL for the current page with some parameters replaced. Empty values are dropped.
     *
     * @param  array<string, string>  $changes
     */
    public function urlWith(array $changes): string
    {
        $query = array_filter([
            'mode' => $this->mode,
            'section' => $this->section,
            'density' => $this->density,
            'radius' => $this->radius,
            'sidebar' => $this->sidebarSize,
            ...$changes,
        ], fn (string $value): bool => $value !== '');

        return route('aura.styleguide').($query === [] ? '' : '?'.http_build_query($query));
    }

    /**
     * Where each design variable is read. Keep in sync with resources/css/app.css.
     *
     * @return array<string, string>
     */
    private function tokenUsage(): array
    {
        return [
            '--aura-panel-padding' => '.aura-card, .aura-group-section, .aura-login-card, .aura-card-bleed ('.__('sparklines').')',
            '--aura-panel-small-padding' => '.aura-card-small',
            '--aura-layout-gap' => '.aura-layout-row/-cell ('.__('panels and fields in every form, widgets').'), .aura-layout-panel, .aura-layout-grid ('.__('dashboard, record layout').')',
            '--aura-section-gap' => '.aura-group-section, '.__('Settings page groups'),
            '--aura-panel-radius' => '.aura-card, .aura-radius-panel ('.__('modal, dropdown, popover, toast, search, kanban column, bulk bar').'), .aura-group-section, .aura-login-card',
            '--aura-control-radius' => '.aura-button, .aura-field, .aura-card-small, .aura-radius-control ('.__('list rows, icon tiles').'), .aura-segmented, '.__('sidebar items'),
            '--aura-sidebar-item-height' => '.aura-sidebar .aura-sidebar-item',
            '--aura-sidebar-item-padding-x' => '.aura-sidebar .aura-sidebar-item',
            '--aura-sidebar-icon-size' => '.aura-sidebar .sidebar-item svg',
            '--aura-sidebar-group-gap' => '.aura-sidebar-groups',
            '--aura-sidebar-heading-gap' => '.aura-sidebar .aura-sidebar-heading',
            '--aura-control-height-xs' => '.aura-control-xs, .aura-control-min-xs',
            '--aura-control-height-sm' => '.aura-control-sm',
            '--aura-control-height' => '.aura-control-base, .aura-control-min-base, '.__('color swatch'),
            '--aura-control-height-lg' => '.aura-control-lg',
            '--aura-control-height-xl' => '.aura-control-xl',
        ];
    }
}
