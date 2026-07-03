# 📋 Noted — Task Schedule & Notes App

A full-featured **Task Scheduling and Notes** application built with **Laravel 11**, **Livewire 3**, **Alpine.js**, and **Tailwind CSS** using a bold **Neo-Brutalism UI** design. Primary color: **Lime**. Light mode only.

---

## ✨ Features

- **Dashboard** — Summary cards (To Do / In Progress / Done / Overdue), active projects with progress bars, top 5 urgent tasks
- **Projects & Tasks** — Tree-view sidebar (Client → Projects), Notion-style task editor with checklist support
- **Notes** — Rich-text notes editor with image/video/link rendering, NoteCredentials vault (encrypted passwords)
- **#Hashtag Filter** — Global tag browser that simultaneously shows tagged Tasks and Notes
- **Neo-Brutalism UI** — Bold lime-primary, thick black borders, harsh drop shadows, pressed button effects

---

## 🗄 Database Structure

```
clients            → id, name, slug
projects           → id, client_id, project_name, slug, status
tasks              → id, project_id, task_name, content (LongText), status, due_date
notes              → id, user_id, title, body (LongText)
note_credentials   → id, note_id, url_login, username, password (encrypted)
hashtags           → id, tag_name (unique)
taggables          → hashtag_id, taggable_id, taggable_type (polymorphic many-to-many)
```

All foreign keys use `->onDelete('cascade')`.  
`NoteCredential.password` is encrypted via Laravel's built-in `encrypted` cast.

---

## ⚙️ Services

| Service | Purpose |
|---------|---------|
| `HashtagParserService` | Extracts `#tags` from content via regex, inserts into `hashtags`, syncs polymorphic relationship |
| `ChecklistService` | Parses `- [ ]` / `- [x]` markdown, tracks progress, toggles items |
| `NoteRenderer` | Converts note body to safe HTML (bold, italic, code, links, images, YouTube embeds, hashtag links) |

---

## 🚀 Installation

```bash
# 1. Clone the repository
git clone https://github.com/gunturborneo31/noted.git
cd noted

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Configure environment
cp .env.example .env
php artisan key:generate

# 5. Create SQLite database
touch database/database.sqlite

# 6. Run migrations
php artisan migrate

# 7. (Optional) Seed with demo data
php artisan db:seed

# 8. Build assets
npm run build
# or for development:
npm run dev

# 9. Start the server
php artisan serve
```

Visit `http://localhost:8000`

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 |
| Reactivity | Livewire 3 |
| UI Interactivity | Alpine.js (bundled with Livewire) |
| CSS Framework | Tailwind CSS 3 |
| UI Style | Neo-Brutalism (lime primary) |
| Database | SQLite (default) / MySQL / PostgreSQL |
| Encryption | Laravel `encrypted` cast (AES-256) |

---

## 📝 Markdown / Content Syntax (Tasks & Notes)

| Syntax | Output |
|--------|--------|
| `#hashtag` | Auto-tagged, clickable |
| `- [ ] item` | Unchecked checklist |
| `- [x] item` | Checked checklist |
| `**bold**` | **Bold** |
| `*italic*` | *Italic* |
| `` `code` `` | Inline code |
| `[text](url)` | Hyperlink |
| `![alt](url)` | Image |
| `https://youtube.com/embed/ID` | Video embed |

---

## 🎨 Neo-Brutalism Design Tokens

```css
/* Borders */      border-4 border-black
/* Shadows */      shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]
/* Buttons */      active:translate-x-[2px] active:translate-y-[2px] active:shadow-none
/* No radius */    rounded-none (default)
/* Primary */      bg-lime-400  (#a3e635)
/* Gradients */    from-lime-400 to-lime-300, from-lime-100 to-white
```

CSS utility classes: `.neo-btn`, `.neo-input`, `.neo-label`, `.neo-card`, `.neo-badge`

---

## 📁 Project Structure

```
app/
  Livewire/
    Dashboard.php          # Dashboard stats, projects, urgent tasks
    ProjectTaskList.php    # Tree sidebar + task list + Notion-style editor
    NoteEditor.php         # Notes CRUD + credentials vault
    HashtagFilter.php      # Global hashtag search/filter
  Models/
    Client.php, Project.php, Task.php
    Note.php, NoteCredential.php, Hashtag.php
  Services/
    HashtagParserService.php  # Regex hashtag extractor + DB sync
    ChecklistService.php      # Markdown checklist parser & toggler
    NoteRenderer.php          # Safe HTML renderer for note bodies

resources/views/
  layouts/app.blade.php      # Main layout (header, nav, footer)
  livewire/
    dashboard.blade.php
    project-task-list.blade.php
    note-editor.blade.php
    hashtag-filter.blade.php

database/migrations/
  *_create_clients_table.php
  *_create_projects_table.php
  *_create_tasks_table.php
  *_create_notes_table.php
  *_create_note_credentials_table.php
  *_create_hashtags_table.php
  *_create_taggables_table.php
```

---

## 🔐 Security

- Passwords in `NoteCredential` are encrypted at rest using `'password' => 'encrypted'` cast (AES-256-CBC via Laravel's `Encrypter`)
- All foreign keys cascade on delete — no orphaned records
- CSRF protection via Livewire's built-in mechanisms
- XSS protection in `NoteRenderer` via `htmlspecialchars()` before applying markdown rules

---

&copy; 2024 Noted App · Built with Laravel 11 + Livewire 3 + Tailwind CSS Neo-Brutalism
