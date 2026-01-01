# Aura CMS Test Improvement Plan

**Goal**: Review and improve all test files to ensure correctness, coverage, and alignment with actual source code behavior.

**Approach**: Follow the 8-step verification loop for each test area. Consult [Pest docs](https://pestphp.com/) for best practices.

## Priority Order
1. **Field Tests** - Core functionality that everything depends on
2. **Resource Tests** - CRUD operations using fields
3. **Livewire/Table Tests** - User interaction layer
4. **Auth/Team Tests** - Security and multi-tenancy
5. **Command Tests** - Development tools

## Test Categories

### 1. Feature Tests - Auth (7 files)
- [ ] `tests/Feature/Auth/AuthenticationTest.php`
- [ ] `tests/Feature/Auth/AuthRoutesTest.php`
- [ ] `tests/Feature/Auth/EmailVerificationTest.php`
- [ ] `tests/Feature/Auth/PasswordConfirmationTest.php`
- [ ] `tests/Feature/Auth/PasswordResetTest.php`
- [ ] `tests/Feature/Auth/PasswordUpdateTest.php`
- [ ] `tests/Feature/Auth/RegistrationTest.php`

### 2. Feature Tests - Aura Core (22 files)
- [ ] `tests/Feature/Aura/AuraLayoutCommandTest.php`
- [ ] `tests/Feature/Aura/AuthSettingsConfigTest.php`
- [ ] `tests/Feature/Aura/BasicConditionalLogicOnFieldsTest.php`
- [ ] `tests/Feature/Aura/ConditionalLogicAsClosureTest.php`
- [ ] `tests/Feature/Aura/ConditionsWithClosuresTest.php`
- [ ] `tests/Feature/Aura/CreatePluginTest.php`
- [ ] `tests/Feature/Aura/CreateResourceFactoryCommandTest.php`
- [ ] `tests/Feature/Aura/CreateResourceMigrationTest.php`
- [ ] `tests/Feature/Aura/CreateResourcePermissionsCommandTest.php`
- [ ] `tests/Feature/Aura/DatabaseToResourcesCommandTest.php`
- [ ] `tests/Feature/Aura/DoNotDeferConditionalLogicTest.php`
- [ ] `tests/Feature/Aura/ExtendUserModelCommandTest.php`
- [ ] `tests/Feature/Aura/FeaturesSettingsConfigTest.php`
- [ ] `tests/Feature/Aura/GlobalsTest.php`
- [ ] `tests/Feature/Aura/InstallationTest.php`
- [ ] `tests/Feature/Aura/InstallConfigCommandTest.php`
- [ ] `tests/Feature/Aura/MakeFieldCommandTest.php`
- [ ] `tests/Feature/Aura/MakeResourceCommandTest.php`
- [ ] `tests/Feature/Aura/MakeUserCommandTest.php`
- [ ] `tests/Feature/Aura/PagesTest.php`
- [ ] `tests/Feature/Aura/ParentConditionalLogicTest.php`
- [ ] `tests/Feature/Aura/PublishCommandTest.php`
- [ ] `tests/Feature/Aura/ResourceEditTest.php`
- [ ] `tests/Feature/Aura/ResourceTest.php`

### 3. Feature Tests - Fields (24 existing + 22 missing = 46 total)
- [ ] `tests/Feature/Fields/AdvancedSelectFieldOptionsTest.php`
- [ ] `tests/Feature/Fields/AdvancedSelectFieldTest.php`
- [ ] `tests/Feature/Fields/AdvancedSelectFieldVariationsTest.php`
- [ ] `tests/Feature/Fields/AdvancedSelectFieldViewsTest.php`
- [ ] `tests/Feature/Fields/AdvancedSelectMetaFieldTest.php`
- [ ] `tests/Feature/Fields/BooleanFieldTest.php`
- [ ] `tests/Feature/Fields/CheckboxFieldTest.php`
- [ ] `tests/Feature/Fields/ComplexFieldsTest.php`
- [ ] `tests/Feature/Fields/DateFieldTest.php`
- [ ] `tests/Feature/Fields/DisplayFieldTest.php`
- [ ] `tests/Feature/Fields/EmailFieldTest.php`
- [ ] `tests/Feature/Fields/HasManyCustomTableSettingsTest.php`
- [ ] `tests/Feature/Fields/HasManyFieldTest.php`
- [ ] `tests/Feature/Fields/NestedFieldsTest.php`
- [ ] `tests/Feature/Fields/NestedGroupFieldsTest.php`
- [ ] `tests/Feature/Fields/NewHasManyFieldTest.php`
- [ ] `tests/Feature/Fields/NumberFieldTest.php`
- [ ] `tests/Feature/Fields/PasswordFieldTest.php`
- [ ] `tests/Feature/Fields/RadioFieldTest.php`
- [ ] `tests/Feature/Fields/SlugFieldTest.php`
- [ ] `tests/Feature/Fields/TagsFieldTest.php`
- [ ] `tests/Feature/Fields/TagsRelationFieldTest.php`
- [ ] `tests/Feature/Fields/TextFieldTest.php`

#### Missing Field Tests (need to create)
- [ ] `tests/Feature/Fields/BelongsToFieldTest.php` - BelongsTo relationships
- [ ] `tests/Feature/Fields/BelongsToManyFieldTest.php` - Many-to-many relationships
- [ ] `tests/Feature/Fields/CodeFieldTest.php` - Code editor field
- [ ] `tests/Feature/Fields/ColorFieldTest.php` - Color picker field
- [ ] `tests/Feature/Fields/DatetimeFieldTest.php` - Date and time field
- [ ] `tests/Feature/Fields/FileFieldTest.php` - File upload field
- [ ] `tests/Feature/Fields/GroupFieldTest.php` - Field grouping
- [ ] `tests/Feature/Fields/HasOneFieldTest.php` - HasOne relationships
- [ ] `tests/Feature/Fields/HeadingFieldTest.php` - Heading display field
- [ ] `tests/Feature/Fields/HiddenFieldTest.php` - Hidden field type
- [ ] `tests/Feature/Fields/HorizontalLineFieldTest.php` - HR field
- [ ] `tests/Feature/Fields/IDFieldTest.php` - ID display field
- [ ] `tests/Feature/Fields/ImageFieldTest.php` - Image upload field
- [ ] `tests/Feature/Fields/JsonFieldTest.php` - JSON data field
- [ ] `tests/Feature/Fields/LivewireComponentFieldTest.php` - Custom Livewire field
- [ ] `tests/Feature/Fields/PanelFieldTest.php` - Panel container field
- [ ] `tests/Feature/Fields/PermissionsFieldTest.php` - Permissions selector
- [ ] `tests/Feature/Fields/PhoneFieldTest.php` - Phone number field
- [ ] `tests/Feature/Fields/RepeaterFieldTest.php` - Repeatable field groups
- [ ] `tests/Feature/Fields/RolesFieldTest.php` - Role selector field
- [ ] `tests/Feature/Fields/SelectFieldTest.php` - Basic select field
- [ ] `tests/Feature/Fields/StatusFieldTest.php` - Status indicator field
- [ ] `tests/Feature/Fields/TabFieldTest.php` - Tab container field
- [ ] `tests/Feature/Fields/TabsFieldTest.php` - Tabs container field
- [ ] `tests/Feature/Fields/TextareaFieldTest.php` - Textarea field
- [ ] `tests/Feature/Fields/TimeFieldTest.php` - Time picker field
- [ ] `tests/Feature/Fields/ViewFieldTest.php` - Custom view field
- [ ] `tests/Feature/Fields/ViewValueFieldTest.php` - Display value field
- [ ] `tests/Feature/Fields/WysiwygFieldTest.php` - WYSIWYG editor field

### 4. Feature Tests - Media (4 files)
- [ ] `tests/Feature/Media/AttachmentTest.php`
- [ ] `tests/Feature/Media/BasicMediaTest.php`
- [ ] `tests/Feature/Media/GenerateImageThumbnailTest.php`
- [ ] `tests/Feature/Media/MediaUploaderSecurityTest.php`

### 5. Feature Tests - Resources (11 files)
- [ ] `tests/Feature/Resource/CreateFieldsTest.php`
- [ ] `tests/Feature/Resource/EditFieldsTest.php`
- [ ] `tests/Feature/Resource/FieldsAfterRepeaterTest.php`
- [ ] `tests/Feature/Resource/ForceCustomMetaOnCustomTablesTest.php`
- [ ] `tests/Feature/Resource/ResourceActionsTest.php`
- [ ] `tests/Feature/Resource/ResourceWithCustomTableAndCustomMetaTest.php`
- [ ] `tests/Feature/Resource/ResourceWithCustomTableTest.php`
- [ ] `tests/Feature/Resource/ResourceWithCustomTableWithoutFillableTest.php`
- [ ] `tests/Feature/Resource/RoleTest.php`
- [ ] `tests/Feature/Resource/UserQueryTest.php`
- [ ] `tests/Feature/Resource/UserTest.php`
- [ ] `tests/Feature/Resource/ViewFieldsTest.php`
- [ ] `tests/Feature/Resource/ViewPostTest.php`

### 6. Feature Tests - Table (11 files)
- [ ] `tests/Feature/Table/BasicTableTest.php`
- [ ] `tests/Feature/Table/CustomTableFilterTest.php`
- [ ] `tests/Feature/Table/SettingsTableTest.php`
- [ ] `tests/Feature/Table/TableFilterTest.php`
- [ ] `tests/Feature/Table/TablePaginationTest.php`
- [ ] `tests/Feature/Table/TableSaveFilterTest.php`
- [ ] `tests/Feature/Table/TableSearchTest.php`
- [ ] `tests/Feature/Table/TableSearchUsersTest.php`
- [ ] `tests/Feature/Table/TableSelectRowsTest.php`
- [ ] `tests/Feature/Table/TableSortingTest.php`
- [ ] `tests/Feature/Table/TableTaxonomyFilterTest.php`

### 7. Feature Tests - Team (8 files)
- [ ] `tests/Feature/Team/CreateTeamTest.php`
- [ ] `tests/Feature/Team/CreateUserTest.php`
- [ ] `tests/Feature/Team/DeleteTeamTest.php`
- [ ] `tests/Feature/Team/InviteUserTest.php`
- [ ] `tests/Feature/Team/ProfileTest.php`
- [ ] `tests/Feature/Team/RegisterTeamTest.php`
- [ ] `tests/Feature/Team/RolesAndPermissionsTest.php`
- [ ] `tests/Feature/Team/TeamTest.php`

### 8. Feature Tests - Commands (3 files)
- [ ] `tests/Feature/Commands/CreateDatabaseMigrationTest.php`
- [ ] `tests/Feature/Commands/MigratePostMetaToMetaTest.php`
- [ ] `tests/Feature/Commands/TransferFromPostsToCustomTableTest.php`

### 9. Feature Tests - Widgets (2 files)
- [ ] `tests/Feature/Widgets/SparklineTest.php`
- [ ] `tests/Feature/Widgets/ValueWidgetTest.php`

### 10. Feature Tests - Policies (1 file)
- [ ] `tests/Feature/Policies/TeamPolicyTest.php`

### 11. Feature Tests - Listeners (1 file)
- [ ] `tests/Feature/Listeners/ModifyDatabaseMigrationTest.php`

### 12. Feature Tests - UI/Layout (20 files)
- [ ] `tests/Feature/ApplyTabsTest.php`
- [ ] `tests/Feature/ApplyWrappersTest.php`
- [ ] `tests/Feature/ConfigTest.php`
- [ ] `tests/Feature/CreateResourceTest.php`
- [ ] `tests/Feature/CustomizeComponentCommandTest.php`
- [ ] `tests/Feature/GlobalSearchTest.php`
- [ ] `tests/Feature/GroupRelationsTest.php`
- [ ] `tests/Feature/MultipleTabsBelowEachOtherTest.php`
- [ ] `tests/Feature/MultipleTabsInPanelInTabsTest.php`
- [ ] `tests/Feature/MultipleTabsInPanelInTabsWithAnotherPanelTest.php`
- [ ] `tests/Feature/MultipleTabsWithPanelsExcludeTest.php`
- [ ] `tests/Feature/NavigationTest.php`
- [ ] `tests/Feature/PanelInTabsTest.php`
- [ ] `tests/Feature/PanelsInTabsTest.php`
- [ ] `tests/Feature/PermissionsTest.php`
- [ ] `tests/Feature/RecursiveFunctionTest.php`
- [ ] `tests/Feature/ResourceEditorTest.php`
- [ ] `tests/Feature/ResourceGroupFieldsTest.php`
- [ ] `tests/Feature/ResourcePanelTest.php`
- [ ] `tests/Feature/ResourceRecursiveGroupingTest.php`
- [ ] `tests/Feature/ResourceTabsTest.php`
- [ ] `tests/Feature/SaveRessourceFieldsTest.php`
- [ ] `tests/Feature/SettingsWithoutTeamsTest.php`
- [ ] `tests/Feature/TableIndexTest.php`
- [ ] `tests/Feature/TabsAfterRepeaterTest.php`
- [ ] `tests/Feature/TabsInPanelInTabsTest.php`
- [ ] `tests/Feature/TabsInPanelTest.php`
- [ ] `tests/Feature/TabsWithFieldsTest.php`
- [ ] `tests/Feature/TeamSettingsTest.php`
- [ ] `tests/Feature/ThemeTest.php`
- [ ] `tests/Feature/UpdateSchemaFromMigrationTest.php`
- [ ] `tests/Feature/UserRoleConditionalIndexFieldsTest.php`

### 13. FeatureWithDatabaseMigrations (4 files)
- [ ] `tests/FeatureWithDatabaseMigrations/MakeUserWithoutTeamsCommandTest.php`
- [ ] `tests/FeatureWithDatabaseMigrations/PagesWithoutTeamsTest.php`
- [ ] `tests/FeatureWithDatabaseMigrations/SettingsWithoutTeamsTest.php`
- [ ] `tests/FeatureWithDatabaseMigrations/WithoutTeamsSchemaTest.php`

### 14. Unit Tests (2 files)
- [ ] `tests/Unit/Fields/AdvancedSelectTest.php`
- [ ] `tests/Unit/InputFieldsTest.php`

## Common Improvement Patterns

### Pest Best Practices to Apply
1. Use status helpers: `assertSuccessful()`, `assertNotFound()`, `assertForbidden()`, etc.
2. Use datasets for validation test variations
3. Clear test names in natural language
4. Proper AAA (Arrange-Act-Assert) flow
5. Use factories instead of hard-coded data
6. Leverage Pest's `it()` and `test()` helpers

### Coverage Gaps to Address
1. Missing edge cases (null, empty, max length)
2. Missing authorization checks
3. Missing validation failure scenarios
4. Missing team scoping tests
5. Missing database transaction rollback scenarios

### Anti-patterns to Fix
1. Hard-coded IDs and data
2. Missing assertions (tests that don't verify behavior)
3. Brittle selectors or assumptions
4. Tests not isolated (state leakage)
5. Over-mocking (testing mocks instead of real behavior)
6. Missing cleanup after tests

## Verification Loop (for each test file)

1. **Read**: Fully read current test(s)
2. **Inspect**: Check corresponding source in `/Users/bajram/Projekte/aura-cms/src/`
3. **Compare**: Find gaps, flaky assertions, outdated expectations
4. **Improve**: Align with actual behavior, add missing coverage
5. **Pest-ify**: Apply Pest 3.x best practices
6. **Re-verify**: Ensure assertions match real outputs
7. **Refine**: Improve clarity, maintainability, isolation
8. **Finalize**: Report changes

## Progress Tracking

### Existing Tests
- **Total Existing Test Files**: ~100+
- **Field Tests**: 24 existing, 29 missing
- **Completed**: 0
- **In Progress**: 0
- **Blocked**: 0

### Missing Coverage
- **Field Types Without Tests**: 29 out of 45 field types
- **Unit Test Coverage**: Only 2 unit test files (need expansion)
- **Integration Tests**: Need more Livewire component tests

## Current Status
- ✅ Composer dependencies installed
- ✅ Test plan created
- ⏳ Ready to start test improvements

## Blockers / Notes

- Some field types may be deprecated or internal-only (need to verify)
- Tests need to be run against actual Aura CMS source at `/Users/bajram/Projekte/aura-cms/src/`
