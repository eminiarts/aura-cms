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
