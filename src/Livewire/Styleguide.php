<?php

namespace Aura\Base\Livewire;

use Aura\Base\ThemeTokens;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
    use WithPagination;

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

    /** @var list<string> */
    public const MODES = ['light', 'dark'];

    #[Url(except: '')]
    public string $density = '';

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
     * Demo rows for the pagination specimen. Uses the real paginator so the real
     * pagination view renders and its buttons work.
     */
    public function demoPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: range(1, 4),
            total: 42,
            perPage: 4,
            currentPage: $this->getPage(),
            options: ['path' => route('aura.styleguide'), 'pageName' => 'page'],
        );
    }

    /**
     * Each appearance setting with the CSS variables it writes and their value per preset.
     *
     * @return array<string, array{label: string, default: string, presets: array<string, array<string, string>>, variables: array<string, array{expert: string|null, usedBy: string}>}>
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
            if (isset($group['presets'][$selected[$setting]])) {
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
                'description' => __('One font family from the theme (--aura-font-sans). The admin uses text-xs to text-2xl.'),
            ],
            'icons' => [
                'label' => __('Icons'),
                'description' => __('<x-aura::icon icon="…" size="xs|sm|base|lg" /> renders 16, 20, 24 or 32 px.'),
            ],
            'buttons' => [
                'label' => __('Buttons'),
                'description' => __('<x-aura::button> with variants as sub-components and sizes through the size prop. Radius follows --aura-control-radius.'),
            ],
            'forms' => [
                'label' => __('Form controls'),
                'description' => __('Inputs from components/input/*. Resource fields wrap them with a label, help tooltip and validation error (fields/wrapper).'),
            ],
            'sizing' => [
                'label' => __('Control sizing'),
                'description' => __('Controls that sit next to each other should share one height. There is no height token: each control sizes itself through padding and line height. Heights below are measured in the browser.'),
            ],
            'badges' => [
                'label' => __('Badges & pills'),
                'description' => __('Three badge shapes are in use: status pills (dot + ring), sidebar badges and trend pills.'),
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
            '--aura-panel-padding' => '.aura-card',
            '--aura-panel-small-padding' => '.aura-card-small',
            '--aura-layout-gap' => __('Settings page panels'),
            '--aura-section-gap' => __('Settings page groups'),
            '--aura-panel-radius' => '.aura-card',
            '--aura-control-radius' => '.aura-button, .aura-card-small, '.__('sidebar items'),
            '--aura-sidebar-item-height' => '.aura-sidebar .aura-sidebar-item',
            '--aura-sidebar-item-padding-x' => '.aura-sidebar .aura-sidebar-item',
            '--aura-sidebar-icon-size' => '.aura-sidebar .sidebar-item svg',
            '--aura-sidebar-group-gap' => '.aura-sidebar-groups',
            '--aura-sidebar-heading-gap' => '.aura-sidebar .aura-sidebar-heading',
        ];
    }
}
