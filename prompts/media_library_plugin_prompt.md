# Media Library Plugin for Aura CMS

## Context & Motivation

The current media library implementation feels unfinished - "halfway to the finish line." It works sometimes but doesn't feel like polished, fully thought-out software. The goal is to create a proper, complete media library that feels professional and handles all edge cases properly.

## Core Principles

1. **Finish what we start** - No half-done features. Everything should feel complete and polished
2. **Beautiful UI** - Modern, clean design inspired by Tailwind UI and shadcn components
3. **Standalone plugin** - Composer-installable, follows Aura plugin architecture
4. **Custom tables** - From experience, custom tables work better than posts table when projects get serious (avoids non-incrementing ID issues during migrations)
5. **Configurable storage** - Support any Laravel filesystem disk (local, S3, FTP, etc.)
6. **Start simple, iterate** - Build MVP first, keep future features in mind

---

## Plugin Architecture

### Package Structure

Follow the Aura CMS plugin pattern:
- Use Spatie Laravel Package Tools for service provider
- Register with `Aura::registerResources()`
- Livewire components for interactivity
- Blade views using Aura component library

Reference the existing Forms plugin at `plugins/aura/forms/` for structure.

The plugin should be separate from core Aura functionality, but we may include it by default in Aura installations (decision pending).

### Database - Custom Tables

Use dedicated custom tables, not the posts table:

**media_items table:**
- id, timestamps
- File info: name, filename, path, disk, mime_type, size, extension
- Image-specific: width, height, alt_text
- Organization: folder_id (nullable foreign key)
- Team scoping: team_id (nullable, for multi-tenancy)

**media_folders table:**
- id, name, slug, parent_id (self-referencing for nesting), team_id, order, timestamps

**media_tags table (Phase 2):**
- id, name, slug, team_id, timestamps
- Plus pivot table for tagging

### Storage Configuration

The plugin must leverage Laravel's filesystem abstraction:

**How it works:**
- User configures which Laravel disk to use in config (local, s3, ftp, etc.)
- The model stores only the relative path
- Laravel's Storage facade handles the actual file operations
- Changing the disk config changes where files are stored/retrieved

**Config options needed:**
- Default disk name (references Laravel's filesystems.php config)
- Base path within the disk
- Allowed file types
- Max file size

**Storage Migration Command:**
When switching from local to S3 (or vice versa), provide an artisan command to migrate existing files from one disk to another. This should:
- Copy files from source disk to destination disk
- Update database records with new disk reference
- Optionally delete from source after successful migration
- Handle failures gracefully with rollback

---

## UI/UX Design

### Design Philosophy

- **Aesthetic**: Tailwind UI components, shadcn/ui style
- **Feel**: Clean, spacious, modern SaaS application
- **Polish**: Every interaction should feel complete and intentional
- **No half-measures**: If a feature exists, it should work perfectly

### Layout

Two-panel layout:
1. **Left sidebar** (collapsible): Folder tree navigation
2. **Main area**: Media grid/list with toolbar

### Grid View (Primary)

This is the default view and should look polished:
- Responsive grid adapting to screen size
- Square thumbnails for images with proper aspect ratio handling
- File type icons for non-images (PDF, DOC, video, etc.)
- Hover overlay showing filename and quick actions
- Checkbox for selection (top-left corner)
- Selection states: click, Ctrl/Cmd+click for multi-select, Shift+click for range
- Double-click to open detail view

### List View (Alternative)

Always available as an option:
- Table-style rows
- Columns: Thumbnail, Name, Type, Size, Date, Actions
- Sortable columns
- Same selection behavior as grid

### Toolbar Features

- Search input (filter by filename)
- View toggle: Grid / List
- Sort dropdown: Date, Name, Size, Type
- Upload button (prominent, primary action)
- Bulk action buttons (appear when items selected)

### File Uploader

This should be exceptional - a highlight of the plugin:
- Large drag-and-drop zone
- Visual feedback when dragging files over (border highlight, background change)
- Progress indicators for each file uploading
- Support multiple file upload simultaneously
- Show thumbnail previews as files upload
- Error handling with clear, actionable messages
- Cancel individual uploads
- Queue management for many files

### Folder Sidebar

- "All Media" at top (shows everything, unfiltered)
- Collapsible nested folder tree
- Create folder button (+ icon)
- Right-click context menu: Rename, Delete, Create subfolder
- Drag media items onto folders to move them
- Visual indication of current/active folder
- Folder counts (number of items)

### Selection & Bulk Actions

When items are selected, show a sticky action bar:
- Selected count indicator ("3 items selected")
- Move to folder button (opens folder picker)
- Add tags button (Phase 2)
- Delete button (with confirmation)
- Download button (if applicable)
- Clear selection button

### Detail/Edit Panel

When clicking on a media item (single click or dedicated button):
- Slide-over panel from right (preferred) or modal
- Large preview of the file
- For images: show the image
- For PDFs: show PDF preview if possible
- For documents: show file icon with basic info
- Editable fields: name, alt text (for images)
- Read-only info: dimensions, file size, type, uploaded date, disk location
- Tags management (Phase 2)
- Current folder location with option to move
- Copy URL button (copies public URL to clipboard)
- Replace file button (upload new file to replace this one)
- Delete button

### Replace File Feature

Users should be able to replace a file while keeping the same database record:
- Upload new file
- Old file gets replaced
- Same URL/path maintained (or updated if filename changes)
- For now: simple replacement, no revision history
- Future consideration: keep revisions (version 2, version 3, etc.)

### Media Picker Modal (Integration with Fields)

When using Image/File fields in resources, clicking "Select from Library" opens a modal:
- Same grid/list view as main library
- Folder navigation available
- Search and filter available
- Multi-select if field allows multiple files
- **Important edge case**: If a previously selected file is on page 3 of 10, the modal should:
  - Either jump to that page and highlight the selected item
  - Or show selected items at the top/in a separate section
  - The current implementation doesn't handle this well - needs proper solution

---

## Functional Requirements

### MVP (Phase 1)

Focus on these first - each one should be COMPLETE:

1. **Media Resource with Custom Table**
   - Dedicated media_items table
   - Model with proper relationships
   - CRUD operations
   - Team scoping (when teams enabled)

2. **Configurable Storage**
   - Config file for disk, path, limits
   - Works with any Laravel filesystem disk
   - Store path in database, disk handles actual storage

3. **Upload**
   - Drag-and-drop uploader
   - Multi-file support
   - Progress indication per file
   - Error handling
   - Validation (file type, size)

4. **Browse**
   - Grid view with thumbnails (polished, not half-done)
   - List view alternative
   - Pagination that works properly
   - Search by filename
   - Sort by date/name/size/type
   - File existence checking (handle missing files gracefully)

5. **Folders**
   - Create/rename/delete folders
   - Nested folders (reasonable depth limit)
   - Move media between folders (drag-drop and bulk action)
   - Folder sidebar navigation

6. **Selection**
   - Multi-select with checkboxes
   - Ctrl+click, Shift+click behavior
   - Bulk delete with confirmation
   - Bulk move to folder

7. **Detail View**
   - View/edit individual media items
   - Replace file functionality
   - Copy URL

8. **Media Picker Integration**
   - Modal for selecting from library in Image/File fields
   - Proper handling of already-selected items across pages

### Phase 2 (Next Iteration)

- Tags system (create tags, tag media, filter by tags)
- Storage migration command (local to S3, etc.)
- Revision history when replacing files
- Advanced filtering (by type, date range, size range)

### Phase 3 (Future)

- Simple image editor (crop, resize) like WordPress
- Document previews (PDF viewer, Word doc preview)
- AI features as separate plugin (media-library-ai):
  - AI image editing with prompts
  - AI upscaling for low-quality images
- Usage tracking (which resources reference which media)
- API endpoints for headless usage
- Duplicate detection

---

## Responsive Images

For images, generate multiple sizes:

**Approach options (needs decision):**

Option A: WordPress-style defined dimensions
- Configure specific sizes: thumbnail (150x150), medium (300x300), large (1024x1024)
- Generate these sizes on upload

Option B: Responsive breakpoints
- Generate for mobile, tablet, desktop
- More fluid, percentage-based

Option C: On-demand generation
- Generate sizes when requested
- Cache generated sizes
- More flexible but requires image processing on request

**Recommendation**: Start with Option A (defined dimensions) as it's simpler and proven. Can evolve later.

The existing ThumbnailGenerator service in Aura can be leveraged for this.

---

## Technical Approach

### Livewire Components

Create modular, focused components:
- **MediaLibrary** - Main orchestrator component
- **FolderTree** - Sidebar folder navigation
- **MediaGrid** - Grid/list display with selection
- **MediaUploader** - File upload handling
- **MediaDetail** - Detail/edit panel
- **MediaPicker** - Modal for field integration

### File Operations

- Use Laravel's Storage facade for all file operations
- This automatically handles different disk types (local, s3, ftp)
- Check file existence before operations
- Handle missing files gracefully in UI

### Thumbnail Generation

- Leverage existing Aura ThumbnailGenerator service
- Generate configured sizes on upload
- Store thumbnail paths in database or generate predictable paths
- Serve optimized thumbnails in grid view

### Error Handling

- Validate files before upload (type, size)
- Handle upload failures gracefully
- Handle missing files (deleted outside system)
- Clear error messages for users

---

## Configuration

Config file should include:

**Storage:**
- disk: 'public' (or 's3', 'ftp', etc. - references Laravel disk)
- path: 'media' (subdirectory within disk)
- allowed_types: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', ...]
- max_file_size: 10240 (KB)

**Thumbnails:**
- sizes: [{name: 'thumb', width: 150, height: 150}, {name: 'medium', width: 300}, ...]
- quality: 80

**UI:**
- default_view: 'grid'
- per_page: 24
- enable_folders: true
- enable_tags: false (Phase 2)

---

## Development Workflow

### Setup

1. Create plugin directory structure following Aura conventions
2. Set up composer.json with proper autoloading
3. Create service provider extending PackageServiceProvider
4. Register with Aura

### Build Order

1. Database migrations and models (custom tables)
2. Basic config file
3. Storage service/helper using Laravel Storage
4. File upload functionality (the star feature)
5. Grid/list view display
6. Folder system
7. Selection and bulk actions
8. Detail/edit panel
9. Media picker modal for fields
10. Polish everything

### Testing

- Feature tests for upload, CRUD, folder operations
- Test with different storage disks (mock S3)
- Test team scoping
- Test edge cases (missing files, large uploads, etc.)

---

## Key Edge Cases to Handle

1. **File deleted outside system**: Check existence, show placeholder or error state
2. **Selected file on different page**: In picker modal, properly indicate/navigate to selected items
3. **Upload failures**: Clear error messages, ability to retry
4. **Large files**: Progress indication, timeout handling
5. **Many files**: Proper pagination, performance
6. **Disk change**: Migration path for existing files
7. **Missing thumbnails**: Generate on demand or show original

---

## Success Criteria

The plugin is successful when:

- Uploading files is delightful (drag-drop, progress, instant feedback)
- Browsing large libraries is fast and intuitive
- Organizing with folders feels natural
- The UI looks premium and modern - not half-finished
- Every feature works completely, no rough edges
- Integration with Image/File fields is seamless
- Storage configuration is flexible (local, S3, etc.)
- The codebase is clean and maintainable for future iterations
