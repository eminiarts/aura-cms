# Aura CMS

A Laravel package providing a content management system with a dynamic resource/field system, team management, and role-based access control.

## Language

**Resource**:
A content type managed by Aura, defined as a PHP class with a declared list of fields.
_Avoid_: model, post type, entity

**Field**:
A typed unit of a resource's data, carrying its input UI, display rendering, and validation.
_Avoid_: attribute, column, property

**Team**:
The tenant boundary. Every resource query is scoped to the current team.
_Avoid_: tenant, workspace, organization

**Teams-off mode**:
A supported operating mode in which the team feature is disabled and the installation is single-tenant. Must be as safe and as fully tested as the default mode.
_Avoid_: single-tenant mode, no-teams

**Global Admin**:
A user with instance-level administrative status. Transcends the tenant boundary: sees and manages all teams and all users, and may enter any team without being a member. Distinct from a Super Admin, whose power is confined to one team.
_Avoid_: super admin, root user, owner

**Super Admin**:
A role-level grant of every permission within the single team where the role is held. Says nothing about other teams.
_Avoid_: global admin, admin (ambiguous)

**Global Role**:
A role defined once for the whole instance, available in every team. The set of global roles is the **Role Catalog**. Only Global Admins define global roles.
_Avoid_: shared role, default role

**Team Role**:
A role defined by a team, existing only within that team. Team super admins manage their team's roles.
_Avoid_: custom role, local role

**Shadowing**:
When a team defines a role with the same slug as a global role, the team's definition replaces the global one inside that team; other teams are unaffected. Deleting the shadow falls back to the global definition. A role's identity within a team is its slug.
_Avoid_: overriding (ambiguous), forking

**Membership**:
A user's belonging to a team, always with exactly one role in that team. Global Admins entering a team they don't belong to are visiting, not members.
_Avoid_: association, assignment

**Invitation**:
A single-use, expiring, role-carrying offer of team Membership addressed to an email. Accepting attaches the existing user with that email, or registers a new one, into the team with the carried role.
_Avoid_: invite link, signup token

**Resource Editor**:
A local-development tool for editing resource definitions from the browser. Not a production feature; it only exists in local environments.
_Avoid_: admin builder, schema editor

**Attachment**:
A stored media item: one uploaded file plus its metadata (title, alt text, mime type, size, dimensions).
_Avoid_: media item, file record, upload

**Media Library**:
The media subsystem as a whole, and specifically the standalone index page listing all attachments. Its short navigation label is "Media".
_Avoid_: media manager, asset library

**Media Picker**:
The modal opened from a media field (Image, File) to select attachments into that field's value.
_Avoid_: media manager, media modal

**Details Panel**:
The drawer/sidebar showing a single attachment's preview and editable metadata. Appears on the Media Library page and inside the Media Picker.
_Avoid_: attachment sidebar, info pane

## Commercial Plugins

**Aura Base**:
The open-source, MIT-licensed Aura CMS package and all capabilities already released or advertised as part of the core CMS.
_Avoid_: free tier, community edition, limited edition

**Plugin Buyer**:
A Laravel agency or developer that purchases an Aura plugin for a client project. The Plugin Buyer selects, installs, configures, and maintains the plugin.
_Avoid_: content editor, site owner, end user

**Customer Account**:
The billable Aura Store identity that owns subscriptions, Project Licenses, registered projects, and Composer credentials. It may represent a one-person developer business or an agency with multiple members.
_Avoid_: User, Team, Stripe customer

**Account Member**:
A User authorized to act within a Customer Account under an assigned account-level role.
_Avoid_: Plugin User, Team Membership, license seat

**Plugin Support**:
Assistance for a Plugin Buyer with installation, configuration, documented usage, supported-version compatibility, security reports, and reproducible plugin defects under an active Project License. It excludes bespoke development, project-specific integration work, unrelated application debugging, and direct support for Plugin Users.
_Avoid_: consulting, custom development, end-user help desk

**Plugin User**:
An administrator or content editor who uses a plugin's capabilities inside an Aura application but does not ordinarily purchase or install it.
_Avoid_: customer, buyer, developer

**Operational Application**:
An Aura application used to run recurring business processes around structured Resources, permissions, forms, workflows, approvals, reporting, and integrations.
_Avoid_: brochure website, marketing site

**Client Portal**:
An Operational Application through which an agency's client and the client's users access scoped data and business processes.
_Avoid_: public website, admin dashboard (too broad)

**Commercial Plugin**:
A separately installable Aura extension that adds a coherent capability beyond Aura Base and that a Plugin Buyer can purchase on its own. Commercial Plugins do not remove or paywall capabilities previously included in Aura Base.
_Avoid_: module, add-on, premium feature

**Official Plugin**:
A Commercial Plugin owned, maintained, supported, and sold by the Aura team. The initial Aura Store contains only Official Plugins.
_Avoid_: community plugin, third-party plugin

**Free Official Plugin**:
An Aura-owned plugin distributed without charge under an open-source license. It supports adoption but does not count toward the Launch Portfolio or Target Portfolio of Commercial Plugins.
_Avoid_: Commercial Plugin, trial, freemium plugin

**Production-ready Plugin**:
An Official Plugin that matches Aura Base's supported runtime matrix; passes install, upgrade, rollback, teams-mode, tenant-isolation, feature, browser, and security verification as applicable; has complete documentation and a tagged semantic release; installs through authenticated Composer; and is demonstrated in an Aura application.
_Avoid_: prototype, beta listing, coming-soon plugin

**Aura Flows**:
The commercial visual workflow-automation plugin and the anchor product of the Launch Portfolio.
_Avoid_: Free Official Plugin, core workflow feature

**Aura Forms**:
The Commercial Plugin for building public, authenticated, or embedded forms that validate and retain Submissions and may map them into Aura Resources. It collects application data; it does not define developer-owned Resource schemas.
_Avoid_: Resource Editor, resource form, contact-form field

**Submission**:
One captured response to an Aura Form, including its field values, processing status, and retention lifecycle.
_Avoid_: Resource, Community Plugin Submission, request payload

**Aura Revisions**:
The Commercial Plugin that preserves successive snapshots of Resource field values so authorized Plugin Users can compare and restore earlier states without erasing later history.
_Avoid_: Activity, audit event, backup

**Revision**:
An immutable snapshot of one Resource's restorable field state at a particular change, attributed to an actor and time.
_Avoid_: activity entry, draft, backup

**Aura Approvals**:
The Commercial Plugin for assigning and recording human review decisions over Resources through configurable multi-step approval policies. It may integrate with Aura Revisions and Aura Flows but remains independently usable.
_Avoid_: automated Flow, permission check, publishing status

**Approval Request**:
A request for one or more designated reviewers to approve, reject, or return a specific Resource state for changes.
_Avoid_: task, notification, Flow run

**Aura Data Exchange**:
The Commercial Plugin for tenant-aware, validated, repeatable import and export of Resource data in interoperable tabular formats such as CSV and XLSX.
_Avoid_: backup, PDF generator, database migration

**Data Mapping**:
A reusable correspondence between columns in an external dataset and Fields on an Aura Resource, including conversion and validation rules.
_Avoid_: migration, field definition, spreadsheet template

**Aura Reports**:
The Commercial Plugin through which Plugin Users build permission-aware reports and dashboards from Aura Resources, relations, aggregations, filters, tables, and charts.
_Avoid_: developer-defined Widget, raw export, analytics tracking

**Report**:
A saved, repeatable query and presentation of operational Resource data for a defined audience.
_Avoid_: dashboard widget, export file, database query

**Aura API**:
The Commercial Plugin for exposing explicitly selected Aura Resources and Fields through policy-aware, tenant-scoped APIs with scoped credentials and generated documentation. Installing it exposes nothing until a Plugin Buyer opts a Resource in.
_Avoid_: internal field endpoint, automatic public CRUD, headless frontend

**API Exposure**:
The explicit contract selecting which Resource operations, Fields, relations, and filters an API client may access.
_Avoid_: Resource registration, serialization, public model

**Aura Search**:
The Commercial Plugin for permission-aware, tenant-scoped indexing and advanced discovery of selected Resources through external search engines. Aura Base's database-backed Global Search remains open source.
_Avoid_: Global Search, database search box, search-engine optimization

**Search Index**:
A derived, rebuildable representation of explicitly selected Resource data held by a configured search engine for fast retrieval, filtering, and faceting.
_Avoid_: database table, source of truth, API Exposure

**Aura Compliance**:
The Commercial Plugin that preserves and verifies append-only evidence of selected Resource, authentication, permission, and administrative events under explicit retention and access policies.
_Avoid_: Activity, Revision, application log

**Audit Record**:
An append-only account of a relevant event, its actor and tenant context, and approved before/after data, protected so later integrity checks can detect alteration.
_Avoid_: activity entry, restorable snapshot, debug log

**Aura Portal**:
The Commercial Plugin for presenting explicitly selected Aura Resources and actions through a separate, branded, policy-aware self-service area for client users. It is not a general website or page builder.
_Avoid_: Aura admin, marketing website, no-code site builder

**Portal Exposure**:
The explicit contract selecting which Resources, Fields, relations, and actions a defined portal audience may access.
_Avoid_: API Exposure, Resource registration, public model

**Aura Store**:
The catalog and purchasing area on `aura-cms.com` for Official Plugins and the Aura Pro Bundle.
_Avoid_: third-party marketplace, Packagist

**Community Plugin Submission**:
A private proposal stored on `aura-cms.com` for manual review, containing the submitter, repository, and plugin description. A submission is neither an Aura Store listing nor a license to sell through Aura.
_Avoid_: community listing, seller account, marketplace product

**Aura Pro Bundle**:
The commercial package that grants access to the complete portfolio of Commercial Plugins at a lower price than purchasing each one separately.
_Avoid_: single plugin, monolithic plugin, enterprise edition

**Grandfathered Price**:
The renewal price retained by an existing Customer Account while its subscription continues without interruption after the public price increases.
_Avoid_: lifetime price, discount code, sale price

**Launch Portfolio**:
The first five production-ready Official Plugins available when Aura Store begins accepting payment.
_Avoid_: roadmap, coming-soon plugins

**Target Portfolio**:
The planned set of ten Official Plugins: the Launch Portfolio plus five later releases. A plugin does not become part of the sellable portfolio until it is production-ready.
_Avoid_: launch inventory, included unfinished plugins

**Optional Plugin Integration**:
Additional behavior enabled when two compatible Commercial Plugins are installed. Every participating plugin remains independently usable and no Commercial Plugin requires another Commercial Plugin.
_Avoid_: paid dependency, required bundle, plugin coupling

**Project License**:
An annual entitlement to use one Commercial Plugin, or the Aura Pro Bundle, in one Licensed Project.
_Avoid_: developer seat, domain license, environment license

**Licensed Project**:
One production Aura application registered to a Customer Account, together with that application's local, CI, preview, and staging environments.
_Avoid_: environment, domain, Teamwork project

**Composer Credential**:
A revocable secret issued for one Licensed Project that authorizes Composer downloads and updates for packages covered by that project's active entitlements. It is not consulted by installed plugins at runtime.
_Avoid_: runtime license key, account password, GitHub token

**Entitlement**:
A Customer Account's current right, created by a purchase or subscription, to assign a Commercial Plugin or the Aura Pro Bundle to a Licensed Project and receive its releases and support.
_Avoid_: payment, installed package, permission

**Package Release**:
An immutable, semantically versioned distribution of one Commercial Plugin made available to entitled Licensed Projects.
_Avoid_: branch, mutable build, GitHub checkout

**Expired Project License**:
A Project License whose paid term has ended. Its installed plugins continue to operate, but the Plugin Buyer no longer receives downloads, updates, new versions, or support until renewal.
_Avoid_: disabled plugin, revoked installation
