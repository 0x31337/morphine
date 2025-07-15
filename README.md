# Morphine PHP Framework

**Morphine** is a lightweight and modular PHP framework designed for clarity, speed, and security. It introduces a disciplined backend architecture while remaining approachable for developers seeking alternatives to heavy frameworks. Morphine is suitable for web applications, CMSs, internal tools, and research-driven platforms.

---

## Core Principles

- **Minimalism with Structure** — Morphine enforces code organization without overengineering.
- **M.O.R.E. Architecture** — A clean division of responsibilities:
  - **Models** → Data retrieval
  - **Operations** → Data manipulation and I/O
  - **Renders** → View logic and data binding
  - **Events** → Routing and flow control
- **Security-Centric Design** — Safe defaults, strict global scope rules, and CLI validation.
- **View-Theming System** — Native templating with named partials, conditionals, and iterable patterns.
- **Extensible CLI** — A shell environment for installation, configuration, and future build tools.

---

## Features

- No third-party dependencies
- Fast deployment and execution
- Structured template organization
- Secure default configuration
- Easily testable and maintainable codebase
- Ideal for both research and production use

---

## Architecture Overview

```
morphine/
├── application/       # Developer space
│   ├── models/        # Data access
│   ├── operations/    # DB mutations / I/O
│   ├── views/         # Logic-bound view classes
│   ├── themes/        # HTML templates + theme assets
│   └── assets/        # Global CSS/JS/image resources
├── base/              # Framework core (not for modification)
│   ├── morphine/      # Engine, routing, rendering, DB, websocket
│   └── cli/           # Command line interface
└── index.php          # Application entry point
```

---

## CLI Interface

Morphine ships with an interactive CLI shell:

```bash
php base/cli/morph.php
```

Available commands:
- `install` — Guides setup of database credentials with validation
- `pack` — (WIP) Project packaging for deployment
- `list` — Shows available commands
- `exit` — Closes the shell

---

## Usage Examples

Morphine can be used to build:

- Custom dashboards
- CMS and admin systems
- API backends
- WebSocket-based applications
- Academic or research tools

---

## Security Highlights

- Input sanitization and validation helpers
- Enforced scope rules on `$GLOBALS`
- CLI setup prevents insecure credential reuse
- Clean separation between view logic and data

---

## Getting Started

```bash
git clone https://github.com/your-org/morphine
cd morphine/base/cli
php morph.php install
```

---

## License

Morphine is open-source under the **MIT License**.

---

## A Note from the Author

Morphine is a framework that prioritizes discipline over abstraction. It was designed to encourage clarity, modularity, and responsibility in backend PHP development — and to scale well from small tools to large, maintainable systems.

