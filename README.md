# 🧠 SecondBrain.ai — AI-Powered Personal Knowledge Engine

![Laravel 12](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.0-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=black)
![OpenAI](https://img.shields.io/badge/OpenAI-GPT--4o--mini-412991?style=for-the-badge&logo=openai&logoColor=white)
![Pest PHP](https://img.shields.io/badge/Pest_PHP-3.x-FF2D20?style=for-the-badge&logo=pest&logoColor=white)

> **SecondBrain.ai** is a modern, high-performance Laravel 12 application designed to capture, organize, and enrich your raw thoughts using artificial intelligence. Simply dump any prompt, meeting note, or rough topic into SecondBrain.ai, and background workers will distill concise titles, executive summaries, tag badges, related note links, and actionable key takeaways.

---

## ✨ Features

- **💡 AI Instant Processing & Enrichment**: Asynchronous background queue processing via `AnalyzeNote` job that auto-generates:
  - **Concise Title**
  - **Executive Summary Callout**
  - **Relevant #Tags**
  - **💡 Actionable Ideas & Key Takeaways**
- **🛡️ Hybrid AI & Smart NLP Engine**: Integrates with OpenAI `gpt-4o-mini` API and includes a local multi-domain NLP classifier fallback for 10+ categories (pet care, tech, cooking, fitness, business, etc.) to guarantee seamless job completion even if OpenAI quotas are reached.
- **🌙 Persistent Dark & Light Mode**: Built-in theme switch with Alpine.js global store and Tailwind CSS `dark:` variant, persisting user preference in `localStorage`.
- **🔍 Full-Text Search (Laravel Scout)**: Instant search across note titles, body content, AI summaries, and tags.
- **🔗 Automatic Note Linking**: Self-referential database relationships (`note_links`) that automatically link related notes sharing common tags.
- **🗑️ Card Deletion**: Single-click note deletion with browser confirmation from both the grid dashboard and note detail pages.
- **🧪 Comprehensive Test Suite**: 31 automated Pest feature tests verifying note creation, queue dispatching, OpenAI mocking, JSON status polling, and note deletion.

---

## 🛠️ Technology Stack

| Component | Technology | Description |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 12 | PHP 8.2 framework with streamlined structure |
| **Authentication** | Laravel Breeze | Blade & Alpine.js auth stack |
| **Database** | SQLite | Lightweight file-based SQL database |
| **Queue Worker** | Database Queue | Asynchronous note analysis background jobs |
| **Search Engine** | Laravel Scout | Full-text indexing for notes & tags |
| **AI Integration** | OpenAI API / Local NLP | GPT-4o-mini JSON API with fallback NLP engine |
| **Frontend Styling** | Tailwind CSS v4 | Class-based dark mode & modern UI design system |
| **Interactivity** | Alpine.js 3.x | Reactive polling and global theme state management |
| **Testing** | Pest PHP 3.x | Modern PHP feature and unit testing framework |

---

## 📁 Application Architecture

```
app/
├── Http/Controllers/
│   └── NoteController.php       # Handles Index, Create, Store, Show, Status, Search, Destroy
├── Jobs/
│   └── AnalyzeNote.php          # Queued job: transitions state, calls AI service, syncs tags & links
├── Models/
│   ├── Note.php                 # Eloquent model with Scout Searchable & self-referential links
│   ├── Tag.php                  # Note tags relationship
│   └── User.php                 # User model with notes relationship
└── Services/
    └── OpenAIService.php        # GPT-4o-mini structured JSON API & multi-domain fallback engine
resources/
├── css/
│   └── app.css                  # Tailwind CSS directives & dark variant configuration
├── js/
│   └── app.js                   # Alpine.js initialization & window.toggleDarkMode handler
└── views/
    ├── components/              # Application logo, nav links, text inputs, modal, dropdowns
    ├── layouts/
    │   ├── app.blade.php        # Main layout with FOUC prevention & dark mode class bindings
    │   └── navigation.blade.php # Top navbar with SecondBrain.ai branding & Dark Mode switch
    └── notes/
        ├── index.blade.php      # Grid dashboard, search bar, status polling, top trash delete icon
        ├── create.blade.php     # Note submission form
        └── show.blade.php       # Detail reading view with AI summary box & related notes
```

---

## 🚀 Getting Started

### 1. Prerequisites
- **PHP**: 8.2 or higher
- **Composer**: 2.x
- **Node.js**: 18.x or higher & npm
- **SQLite**: Enabled in PHP (`php-sqlite3` / `pdo_sqlite`)

---

### 2. Installation Steps

1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-username/Second-Brain.git
   cd Second-Brain
   ```

2. **Install PHP & Node dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment File**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set Up OpenAI API Key (Optional)**:
   Add your OpenAI key to `.env`:
   ```env
   OPENAI_API_KEY=sk-proj-your-openai-api-key-here
   ```
   *(Note: If no key is provided or if your quota is exceeded, SecondBrain.ai automatically uses its smart local NLP analyzer).*

5. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

6. **Build Frontend Assets**:
   ```bash
   npm run build
   ```

---

### 3. Running Locally

To run the application locally with dev server, background queue worker, and Vite asset watcher:

```bash
composer run dev
```

Alternatively, run each process in separate terminal windows:
```bash
# Terminal 1: Laravel Dev Server
php artisan serve

# Terminal 2: Queue Worker for AI background processing
php artisan queue:work

# Terminal 3: Vite Dev Server
npm run dev
```

Open your browser and navigate to `http://localhost:8000`.

---

## 🧪 Testing & Code Quality

Run the automated Pest test suite:

```bash
php artisan test --compact
```

Format PHP code according to Laravel guidelines:

```bash
vendor/bin/pint --dirty --format agent
```

---

## 📄 License

This project is open-sourced software licensed under the [MIT License](LICENSE).
