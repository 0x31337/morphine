# ⚙️ Morphine Framework

**Morphine** is a clean, fast, and extensible PHP framework designed for modern backend dashboards, surveillance panels, and internal tools — without Laravel’s bloat.  
It follows the **M.O.R.E. architecture**: `Models`, `Operations`, `Renders`, and `Events`, ensuring logical separation of functionality and ease of extension.

> “Inject Morphine into your workflow — eliminate the pain of slow, bloated frameworks.”

---

## 🧠 Architecture: M.O.R.E.

| Component   | Purpose                                                      |
|------------|--------------------------------------------------------------|
| `Models`   | Retrieve data from the database or other sources             |
| `Operations` | Mutate, insert, delete data or trigger heavy I/O processes |
| `Renders`  | Smart view controllers that bind templates with data         |
| `Events`   | Define user events, system hooks, routing & interaction flow |

This design promotes modularity, security, and maintainability — ideal for clean project scaling and academic-grade system design.

---

## 📂 Project Structure

morphine/
├── application/ # User-level code (your sandbox)
│ ├── assets/ # Global static files
│ ├── models/ # Data readers (Model Layer)
│ ├── operations/ # Mutators / I/O operations
│ ├── views/ # View controllers (Render Layer)
│ └── themes/ # Theme-based HTML templates
├── base/ # Morphine Core (Engine)
│ ├── engine/ # Core systems: DB, Security, Autoloader
│ ├── events/ # Routing, Pages, Listeners
│ └── renders/ # View abstraction layer
└── index.php # Entry point


---

## 📐 Template & View Conventions

- Templates reside in `themes/{themeName}/{viewName}/`
- Naming follows semantic conventions:
  - `viewname.tpl.html` — Main view template
  - `viewname.iterable.tpl.html` — For repeating components
  - `viewname.conditional.tpl.html` — Conditionally-rendered blocks
  - `viewname.CustomName.tpl.html` — Specific-purpose blocks

Each View has a matching PHP class (`views/`) that inherits from `Handler`, declaring:
- Main view
- Partial views
- Data-binding logic
- Input rendering (if applicable)

---

## ✨ Features

- ✅ **M.O.R.E. Architecture** — Clear roles for each component  
- ⚡ **Lightweight & Fast** — Minimal dependencies, fast boot time  
- 🎨 **Themable** — UI rendering is decoupled from logic  
- 🔄 **Global & Theme-specific Assets**  
- 🔌 **Hooks and Extensibility** for plugin developers  
- 🛠️ **CLI Support** via `base/cli/` for automation

---

## 🆚 Why Not Laravel?

| Feature                    | Morphine                    | Laravel                   |
|---------------------------|-----------------------------|---------------------------|
| Design Philosophy         | Logic-first (M.O.R.E.)      | Opinionated (MVC)         |
| Learning Curve            | Low                         | Moderate                  |
| Dashboard-oriented        | Built-in                    | Requires external packages|
| Template Engine           | Native HTML + placeholders  | Blade (custom syntax)     |
| Overhead / Dependencies   | Minimal                     | Heavy                     |
| Academic Transparency     | Full                        | Abstracted                |

Morphine is built to be **learnable**, **extensible**, and **analyzable** — perfect for academic projects, security platforms, or rapid prototyping.

---

## 🔧 Quickstart

```bash
git clone https://your-repo-url morphine
cd morphine

# Set entry point in globals.php:
# $GLOBALS['App.EntryPoint'] = '/your/path';

# Start writing:
# - Define Models for reading data
# - Write Operations for actions
# - Create View classes and templates

👨‍💻 Developer Guidelines

    Use Models only to read data

    Use Operations to mutate or delete data

    Views should follow Handler structure and load corresponding .tpl.html

    Avoid modifying the base/ engine unless you’re contributing to Morphine core

    Use hooks and plugins for extending the core behavior

🏆 Why Use Morphine for Your MSc or Company Project?

    📜 Academic Alignment — Framework design emphasizes clarity, security boundaries, and separation of concerns

    💼 Enterprise Fit — Suitable for dashboards, internal systems, and security consoles

    📊 Whitepaper-Ready — Clean architecture facilitates documentation and research publishing

    🧠 Deep Customizability — No magic, every file is yours to understand

📘 License

MIT License — use it, break it, fix it, share it.
💬 Get Involved

Contributions, plugins, themes, and ideas are welcome.
Fork, star, and let’s build a painless PHP future together.

    Morphine — built for developers who care about structure.


---

Let me know if you'd like me to:
- Add logo/badge artwork
- Prepare a `CONTRIBUTING.md` or plugin system doc
- Create a whitepaper abstract or documentation site (e.g., for GitHub Pages)
