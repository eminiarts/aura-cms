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
