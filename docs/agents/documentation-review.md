# Documentation review

Reviewed on 2026-09-10 against local Aura main, starting at `ddddd28f`.

## Scope and baseline

Each of the 38 public documentation files had its own page agent. That covers the 36 guides in [the index](../index.md), the index itself, and the Flows boundary page. Internal architecture decisions, agent notes, and the Markdown renderer sample were not rewritten.

The package and website now contain matching copies of every public page. The review used the globally installed `unslop` skill. It checked source APIs, storage rules, authorization, commands, examples, links, and screenshots. The public beta remains distinct from current main in the installation guidance.

`aura-new` now uses a Composer path repository for the local Aura checkout. Its existing Article was retained. The quick-start Movie and Product examples were added and tested there.

## Source fixes completed

| Area | Result |
| --- | --- |
| Two-factor login | Confirmed accounts stay unauthenticated until OTP or recovery succeeds. Guest challenge routes use Fortify's canonical names. Intended redirects, remember requests, and Aura's `LoggedIn` event work after full authentication. |
| OTP throttling | A fresh app gets Aura's default limiter even when its Fortify config omits one. Custom named limiters remain supported. |
| Authentication routes | Enrollment uses Aura's password-confirmation page. Verification notifications generate signed Aura URLs. |
| Team policies | Membership and invitation grants use the target Team. Global Admin visitation respects the Team view toggle without requiring a Membership. |
| Role assignment | The User Roles field rejects hidden, shadowed Global Role IDs. |
| Team deletion | Invitation and option cleanup targets the deleted Team after current-team reassignment. |
| Table selection | Select-all and bulk mutations use the active filters and search. Date and Datetime empty predicates remain active without a value. |
| Datetime filters | The UI emits the supported date operators and accepts legacy range aliases. |
| Schema updates | Both quote styles and generated hyphenated identifiers work. Unsupported declarations fail before mutation. Teams-off generation omits `team_id`. |
| Editor failure recovery | Failed schema work restores the resource definition and previous migration, or removes a newly created migration. It does not report success. |
| Editor persistence | The writer supports `getFields(): array`, checks generated PHP syntax, and refuses unsupported return expressions before dispatching schema changes. |
| Configuration | The dynamic layout alias resolves, configuration changes clear the config cache, and installation output uses the registered dashboard URL. |
| Templates | Multiword template slugs resolve through StudlyCase. |

The independent code review found additional empty-date and editor recovery cases. Those findings were reproduced, fixed, and accepted on recheck.

The fixes are committed locally:

| Commit | Scope |
| --- | --- |
| `fa91ab08` | Configuration and template resolution |
| `e10dcfbd` | Two-factor login and authentication routes |
| `cf188dc1` | Team authorization, role assignment, and deletion cleanup |
| `60899bed` | Filtered table selection and date predicates |
| `cb2e9f49` | Schema parsing and Resource Editor recovery |

## Verification

- The final combined selection passed 201 tests with 630 assertions. One existing conditional test was skipped. The Team policy tests passed again after the static-analysis correction.
- The complete PHPStan analysis passes. Obsolete baseline expectations were removed or reduced where errors disappeared. No new suppressions were added.
- Pint and whitespace checks pass for the changed code.
- All 38 documentation pages were checked through the local renderer. Local links, anchors, image paths, and counterpart files were checked. Thirty-five standalone PHP examples passed syntax checks.
- The Movie tutorial was exercised in the browser. Rich text, dates, rating, status, and a new tag saved and appeared on the detail page. Database checks confirmed core values in `posts`, extra values in `meta`, and the tag link in `post_relations`.
- The Product migration was generated and inspected, then applied in Aura New. A Product saved through the browser, and the documented numeric query returned it from the custom table with meta disabled.

This was a documentation and focused implementation review. It was not a full-suite run, production deployment, or claim that every package feature is complete.

## Remaining work for later waves

The following items came from source review unless a runtime check is stated. They are candidates or known limitations to validate before changing behavior. The corresponding guides now avoid promising unsupported behavior.

| ID | Area | Next work |
| --- | --- | --- |
| N1 | Dashboard | The browser confirmed that one statistic card shares a row with recent items and pushes the actions panel below, leaving unused space. Fix row structure, clipped action labels, and the stale version label. Check empty, populated, narrow, and restricted-user states. |
| N2 | Field controls | Verify and complete Color native mode, Time seconds, Select/Status multiple flags, Repeater limits, dynamic Checkbox/Status options, and the HasOne editor. |
| N3 | Media | Validate configured attachment models and custom visibility in listings, server-side picker limits, disk-aware paths, oversized-thumbnail MIME types, and the client size message. |
| N4 | Tables and widgets | Complete or revise public saved-filter controls, `addFilter()` payloads, Kanban ordering, all-time/custom widget date ranges, and widget registration config. |
| N5 | Preferences and cache | Define application/Everyone scope behavior and nullable fallback. Test explicit Team contexts and per-Team user-option cache identity. |
| N6 | Generators and plugins | Check custom discovery namespaces, field-plugin edit/registration output, unsupported facade aliases, and generated package dependencies against the supported stack. |
| N7 | Conversion tools | Reproduce the loaded-class state problem in combined posts-to-custom conversion. Standard Eloquent resources trigger the full-post-row merge, so transfer payloads can exceed generated schemas. A transfer also needs explicit ID, relation, retry, and transaction handling before it can be presented as a general migration path. |
| N8 | Email identity | Test mixed-case registration, login/reset lookup, and existing-user invitation detection on case-sensitive databases. |
| N9 | Remaining interface details | Check the optional title control binding, duplicate profile field slug, avatar behavior, notification entry point/payloads, and global-search keyboard/bookmark edge cases. |
| N10 | Catalog and permission helpers | Check nullable global-slug uniqueness and permission generation for resources using inherited slug declarations. |

The dashboard has been assessed, not redesigned in this wave. The next implementation pass can start with N1 while the remaining component work is prioritized from these findings.
