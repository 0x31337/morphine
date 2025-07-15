# ⚙️ Morphine Framework

**Morphine** is a fast, modular, and academically-oriented PHP framework built for backend dashboards, surveillance systems, and internal tooling.  
It is designed with the **M.O.R.E.** architecture in mind — offering a logical and maintainable alternative to bulky frameworks like Laravel.

> “Inject Morphine into your workflow — eliminate the pain of bloated development.”

---

## 🧠 Architecture: M.O.R.E.

| Component   | Role                                                                 |
|------------|----------------------------------------------------------------------|
| `Models`   | Read data from databases or external sources                         |
| `Operations` | Mutate, insert, or delete data; also for aggressive I/O operations |
| `Renders`  | Handle view logic and link template data                             |
| `Events`   | System routing, dispatching, and behavioral hooks                    |

This architecture ensures **modularity**, **security**, and **ease of contribution**, making it ideal for both production and academic environments.

---

## 📁 Project Structure

```
morphine/
├── application/
│   ├── assets/          # Global JS/CSS/img files
│   ├── models/          # Data readers (Models)
│   ├── operations/      # Writers, mutators, file handlers (Operations)
│   ├── views/           # View classes extending the Handler
│   └── themes/          # HTML templates grouped by theme
├── base/
│   ├── engine/          # Core logic (DB, autoloaders, renders, security)
│   ├── events/          # Routing, dispatching, and hooks
│   └── renders/         # Rendering abstractions
└── index.php            # Application entry point
```

---

## 🖼 Template Naming Conventions

- `viewname.tpl.html` → Main template
- `viewname.iterable.tpl.html` → Loop rendering
- `viewname.conditional.tpl.html` → Conditional blocks
- `viewname.CustomName.tpl.html` → Specialized blocks

Each template has a corresponding PHP class in `application/views/` that follows a structured pattern:
- `set_main_view()`
- `set_partial_views()`
- `set_views_data()`
- `load_data_models()`
- `load_input_views()`

---

## ✨ Key Features

- ✅ **Clear architecture (M.O.R.E.)**
- ⚡ **Lightweight & fast loading**
- 🎨 **Themable design**
- 🔌 **Hookable & extensible**
- 🔒 **Security-focused**
- 🛠 **CLI utilities available in `/base/cli/`**

---

## 🆚 Morphine vs Laravel

| Feature                     | Morphine              | Laravel               |
|----------------------------|------------------------|------------------------|
| Design Philosophy          | M.O.R.E. (clean logic) | MVC (opinionated)     |
| Template System            | Native HTML + Tags     | Blade (custom DSL)    |
| Dependency Overhead        | Very low               | High                  |
| Dashboard/App Focus        | Native support         | Requires packages     |
| Educational Transparency   | Full                   | Abstracted            |
| Performance                | High                   | Moderate              |

---

## 🚀 Quickstart

```bash
git clone https://your-repo-url morphine
cd morphine

# Set your entry point in base/engine/globals.php:
# $GLOBALS['App.EntryPoint'] = '/your/project/path';

# Start building:
# - Define Models for reads
# - Use Operations for writes/mutations
# - Link templates through Views
```

---

## 🧭 Developer Guidelines

- Only use **Models** for reading
- Only use **Operations** for mutating or deleting
- All Views must extend the `Handler` class
- Do **not** touch core files in `/base/`
- Use hooks & plugin API for extensibility
- Global assets in `/application/assets`, theme-specific assets under `/themes/{theme}/assets`

---

## 🏛 Why Morphine for MSc or Enterprise Use?

- 📜 **Academic Clarity** – Designed to be easily understood and documented
- 🛡️ **Security-first** – Encourages separation of read/write responsibilities
- 🧠 **Research-Ready** – Clean enough to base theses, whitepapers, or advanced research on
- ⚙️ **Modular Design** – Developers contribute independently to Models, Ops, and Renders

---

## 📘 License

MIT — Free to use, modify, and distribute.

---

## 💬 Contribute

Got an idea, fix, or feature?  
Fork the repo, make your changes, and submit a pull request.  
Morphine is built for those who care about structure and clarity in their code.

> Built for developers, researchers, and visionaries. Morphine: because backend development shouldn’t hurt.
