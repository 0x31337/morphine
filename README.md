# ⚡ Morphine Framework

**Morphine** is a lightweight, high-performance PHP framework that prioritizes clarity, speed, and maintainability. With an architecture inspired by modern software engineering principles, Morphine offers a refreshing approach to web development — simpler than raw PHP, but lighter and more intuitive than traditional frameworks like Laravel or Symfony.

Whether you’re building a CMS, admin panel, REST API, dashboard, or academic research tool — Morphine helps you move fast, write clean code, and stay in full control.

---

## ✨ Why Morphine?

- 🧠 **M.O.R.E. Architecture** — A clear separation between Models, Operations, Renders, and Events for improved readability and maintainability.
- 🔐 **Security First** — Designed with secure defaults, guardrails against common mistakes, and guidance to prevent unsafe coding practices.
- ⚡ **No Boilerplate Overhead** — Simple to set up, fast to execute, and easy to understand.
- 🎨 **Powerful Theming System** — Native templating with support for partials, iterables, and conditionals without third-party dependencies.
- 🛠 **CLI Tools Included** — Ship faster with built-in command-line tooling for setup, asset packaging, and installation.

---

## 🧠 M.O.R.E. Design Pattern

Morphine is built around the **M.O.R.E.** architecture:

| Layer      | Responsibility                                     |
|------------|-----------------------------------------------------|
| **Models**     | Retrieve and query data from the database or other sources |
| **Operations** | Perform I/O operations, mutations, insertions, deletions    |
| **Renders**    | Bind backend logic to frontend views and templates         |
| **Events**     | Dynamically control routing, execution flow, and states    |

This pattern keeps responsibilities distinct, scalable, and testable — perfect for long-term growth or collaboration.

---

## 🔐 Security by Design

Security is not an afterthought. Morphine includes:

- Guard clauses in CLI to prevent insecure DB setups
- Strict globals initialization to avoid accidental overrides
- Protected access to internal engine components
- Input helpers and sanitization tools for safe request handling

---

## 🖥 Built-In CLI Interface

Morphine ships with a powerful but minimal CLI (`morph`) to assist with setup and packaging:

```bash
php morph.php
```

Supported commands:

- `install` – Set up your database with guided input and automatic validation
- `list` – Show available CLI commands
- `pack` – (In progress) Prepare your app for deployment
- `exit` – Quit the CLI

The CLI ensures secure configurations by validating usernames, passwords, and database names during install — preventing dangerous reuse patterns.

---

## 🧱 Project Structure

```
morphine/
├── application/        # Your app lives here
│   ├── models/         # Data retrieval logic
│   ├── operations/     # Data mutations and I/O
│   ├── views/          # PHP classes tied to templates
│   ├── themes/         # Templated HTML + theme assets
│   └── assets/         # Global CSS, JS, images
├── base/               # Framework core
│   └── morphine/       # Engine, rendering, database, routing
├── index.php           # App entry point
└── base/cli/           # Morphine CLI and tools
```

---

## 🌐 Use Cases

- Content Management Systems (CMS)
- Dashboards & admin panels
- WebSocket-enabled control panels
- Internal tools & utilities
- Research-oriented web applications

---

## 🚀 Getting Started

```bash
git clone https://github.com/your-org/morphine
cd morphine/base/cli
php morph.php install
```

---

## 📄 License

Morphine is released under the **MIT License** — free for personal, commercial, and academic use.

---

## 🤝 Contributing

Morphine is young, growing, and open to contributors. If you’re passionate about backend architecture, security, or CLI tools, we’d love to hear from you.

---

> “Morphine is not just a framework — it's an approach to backend clarity, control, and composability.”

