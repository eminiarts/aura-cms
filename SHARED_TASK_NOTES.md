# SHARED TASK NOTES - Test Improvement Project

## Current Status (2026-01-01)
- ✅ Created comprehensive test improvement plan in `improve_tests_plan.md`
- ✅ Installed composer dependencies
- ✅ Identified test structure: ~100+ test files across Feature, Unit, and special test categories
- ✅ Fixed ImageServiceProvider issue (changed to ImageServiceProviderLaravel)
- ⚠️ Tests are still failing (532 failures) - need further investigation

## Immediate Next Steps
1. **Continue Investigating Test Failures**
   - All 532 tests are failing with same error pattern
   - Run individual test with verbose output to diagnose root cause
   - Check if this is a database/migration issue or configuration problem

2. **Start Field Test Improvements (Priority 1)**
   - Begin with `tests/Feature/Fields/TextFieldTest.php` as baseline
   - Verify against source: `/Users/bajram/Projekte/aura-cms/src/Fields/Text.php`
   - Apply the 8-step verification loop from plan
   - Use Pest best practices (datasets, status helpers, clear naming)

3. **Create Missing Field Tests**
   - 29 field types have no tests at all (see plan for full list)
   - Start with most commonly used: Select, Textarea, Image, File, Datetime

## Key Files to Reference
- **Test Plan**: `improve_tests_plan.md` - Full TODO list and methodology
- **Source Code**: `/Users/bajram/Projekte/aura-cms/src/` - Actual implementation to test against
- **Test Helpers**: `tests/Pest.php` - Helper functions like createSuperAdmin()
- **Base Test**: `tests/TestCase.php` - Base test configuration (needs fix)

## Testing Commands
```bash
# Run all tests
vendor/bin/pest --parallel

# Run specific test file
vendor/bin/pest tests/Feature/Fields/TextFieldTest.php

# Run test group
vendor/bin/pest --group=fields

# Run with coverage (when working)
XDEBUG_MODE=coverage vendor/bin/pest --coverage
```

## Known Issues
1. ImageServiceProvider class not found - blocking all tests
2. Only 2 unit test files exist - need major expansion
3. 29 field types have no test coverage at all

## For Next Developer/Iteration
- Fix the ImageServiceProvider issue first (check TestCase.php line 98)
- Then systematically work through field tests using the plan
- Each test should be verified against actual source code behavior
- Follow Pest 3.x conventions from https://pestphp.com/