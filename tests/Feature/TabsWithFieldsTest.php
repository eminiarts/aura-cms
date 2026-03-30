<?php

use Aura\Base\Resource;

class TabsWithFieldsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'User details',
                'slug' => 'tab-user',
                'global' => true,
            ],
            [
                'name' => 'Personal Infos',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'user-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Name*',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'E-Mail*',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|email',
                'on_index' => true,
                'slug' => 'email',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Geburtsdatum*',
                'type' => 'Aura\\Base\\Fields\\Date',
                'validation' => '',
                'on_index' => true,
                'slug' => 'geburtsdatum',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'Roles',
                'slug' => 'roles',
                'resource' => 'Aura\\Base\\Resources\\Role',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'validation' => 'required',
                'conditional_logic' => function () {
                    return auth()->user()->hasAnyRole(['administrator', 'admin']);
                },
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
            ],

            [
                'name' => 'Member aktiv',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => function () {
                    return auth()->user()->hasAnyRole(['administrator', 'admin']);
                },
                'on_index' => false,
                'slug' => 'is_member',
                'instructions' => 'Ist der Benutzer ein Member?',
            ],

            [
                'name' => 'Rechnungsadresse',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'invoice-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Unternehmen',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'invoice_company',
                'on_index' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'E-Mail fuer Rechnung',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'invoice_email',
                'on_index' => false,
                'style' => [
                    'width' => '100',
                ],
                'instructions' => 'E-Mail fuer Rechnung.',
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'invoice_name',
                'style' => [
                    'width' => '100',
                ],
                'on_index' => false,
            ],
            [
                'name' => 'Adresszeile 1*',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'invoice_addressline_1',
                'on_index' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Adresszeile 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'invoice_addressline_2',
                'on_index' => false,
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'PLZ*',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'invoice_postcode',
                'on_index' => false,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Ort*',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'invoice_city',
                'on_index' => false,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Land',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => false,
                'slug' => 'invoice_country',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'Password',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => 'nullable|min:8',
                'conditional_logic' => [],
                'slug' => 'password',
                'on_index' => false,
                'on_view' => false,
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Oeffentliches Profil',
                'slug' => 'tab-profile',
                'global' => true,
            ],
            [
                'name' => 'Infos',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'profile-infos-panel',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'Profil oeffentlich',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'slug' => 'public_profile',
                'instructions' => 'Duerfen andere Member dein Profil sehen?',
            ],

            [
                'name' => 'Angezeigter Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'slug' => 'display_name',
                'style' => [
                    'width' => '60',
                ],
            ],
            [
                'name' => 'Art des Accounts',
                'placeholder' => 'Art des Accountes auswaehlen...',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',

                'options' => [
                    'Privatperson' => 'Privatperson',
                    'Unternehmen' => 'Unternehmen',
                ],

                'slug' => 'account_type',
                'style' => [
                    'width' => '60',
                ],
                'defer' => false,
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Firma',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',

                'slug' => 'firma',
                'style' => [
                    'width' => '60',
                ],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],

            [
                'name' => 'Jobbezeichnung',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'slug' => 'job_title',
                'style' => [
                    'width' => '60',
                ],
            ],
            [
                'name' => 'Kontakt E-Mail',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'contact_email',
                'on_index' => false,
                'style' => [
                    'width' => '60',
                ],
            ],
            [
                'name' => 'Telefon',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'phone',
                'on_index' => false,
                'style' => [
                    'width' => '60',
                ],
            ],
            [
                'name' => 'Website URL',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'website_url',
                'on_index' => false,
                'style' => [
                    'width' => '60',
                ],
            ],
            [
                'name' => 'LinkedIn',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'linkedin',
                'on_index' => false,
                'style' => [
                    'width' => '60',
                ],
            ],
            [
                'name' => 'Instagram',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'slug' => 'instagram',
                'style' => [
                    'width' => '60',
                ],
            ],
            [
                'name' => 'Beschreibung',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'on_index' => false,
                'slug' => 'job_description',
                'style' => [
                    'width' => '60',
                ],
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => '2FA',
                'slug' => '2fa-tab',
                'global' => true,
            ],
            [
                'name' => '2 Factor Authentication',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => '2fa-panel',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => '2FA',
                'type' => 'Aura\\Base\\Fields\\LivewireComponent',
                'component' => 'aura::two-factor-authentication-form',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => '2fa',
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Delete',
                'slug' => 'delete-tab',
                'global' => true,
            ],
            [
                'name' => 'Delete Account',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'user-delete-panel',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Delete View',
                'type' => 'Aura\\Base\\Fields\\View',
                'view' => 'aura::profile.delete-user-form',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'delete-view',
            ],

        ];
    }
}

test('complex form fields are grouped into single Tabs wrapper', function () {
    $model = new TabsWithFieldsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toBeArray()
        ->and($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs');
});

test('complex form has correct number of tabs', function () {
    $model = new TabsWithFieldsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'])->toHaveCount(4);
});

test('tabs have correct names in order', function () {
    $model = new TabsWithFieldsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields[0]['fields'][0]['name'])->toBe('User details')
        ->and($fields[0]['fields'][1]['name'])->toBe('Oeffentliches Profil')
        ->and($fields[0]['fields'][2]['name'])->toBe('2FA')
        ->and($fields[0]['fields'][3]['name'])->toBe('Delete');
});

test('first tab contains panel with nested fields', function () {
    $model = new TabsWithFieldsTestModel;
    $fields = $model->getGroupedFields();

    $userDetailsTab = $fields[0]['fields'][0];
    $firstPanel = $userDetailsTab['fields'][0];

    expect($firstPanel['name'])->toBe('Personal Infos')
        ->and($firstPanel['type'])->toBe('Aura\Base\Fields\Panel');
});

test('panels contain text fields', function () {
    $model = new TabsWithFieldsTestModel;
    $fields = $model->getGroupedFields();

    $userDetailsTab = $fields[0]['fields'][0];
    $personalInfosPanel = $userDetailsTab['fields'][0];

    // Check that there are fields inside the panel
    expect($personalInfosPanel['fields'])->toBeArray()
        ->and($personalInfosPanel['fields'])->not->toBeEmpty();

    // Find the name field
    $nameField = collect($personalInfosPanel['fields'])->firstWhere('slug', 'name');
    expect($nameField)->not->toBeNull()
        ->and($nameField['name'])->toBe('Name*')
        ->and($nameField['type'])->toBe('Aura\Base\Fields\Text');
});

test('global tabs are properly identified', function () {
    $model = new TabsWithFieldsTestModel;
    $fields = $model->getGroupedFields();

    // Each tab should have been set as global
    foreach ($fields[0]['fields'] as $tab) {
        expect($tab['type'])->toBe('Aura\Base\Fields\Tab');
    }
});
