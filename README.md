<p align="center">
  <img src="https://github.com/user-attachments/assets/cafab220-2104-44b3-9893-44de2178e8d6" width="600" alt="Morphine Logo"/>
</p>

## Morphine PHP Framework

**Morphine** is a modern, minimalist PHP framework designed for developers who want the clarity of raw PHP, but with the structure, power, and security of a well-designed backend system. Whether you're building a CMS, a dashboard, or a full web application, Morphine offers a scalable foundation with zero learning curve bloat.

---

## Why Morphine?

Morphine offers what most developers need, and nothing they don’t:

- ⚡ **Fast and lightweight** — No third-party dependencies, no overhead.
- 🧠 **Thoughtful architecture** — Clear separation of responsibilities through the M.O.R.E. model.
- 🔒 **Security-first** — Enforced safe practices, controlled globals, and credential integrity checks.
- 🎯 **Easy to learn, easy to maintain** — No magic, no black boxes.
- 🎨 **Powerful view system** — Flexible theming and templating built in.
- 🧰 **CLI-ready** — Interactive CLI for setup, packaging, and management.

You’ll spend more time building — not configuring or debugging framework quirks.

---

## M.O.R.E. Architecture

Morphine adopts a clean and efficient design pattern:

| Layer      | Role                                    |
|------------|-----------------------------------------|
| **Models** | Read-only data retrieval                |
| **Operations** | Write, update, delete, and I/O tasks   |
| **Renders** | View classes that bind data to templates |
| **Events**  | Handle routing, dispatching, and flow   |

Each layer has a clear responsibility, which enforces separation of concerns and improves testability.

Please refer to this [whitepaper](https://github.com/user-attachments/files/21241161/whitepaper.pdf) to know MORE.
---

## Directory Structure

```
morphine/
├── application/         # Userland code
│   ├── models/          # Data access
│   ├── operations/      # Logic + write actions
│   ├── views/           # View controllers
│   ├── themes/          # Templates + view assets
│   └── assets/          # Global static resources
├── base/                # Morphine core (do not edit)
│   ├── morphine/        # Engine, routing, renderers, DB
│   └── cli/             # Morph shell (interactive CLI)
└── index.php            # Entry point
```

This structure keeps your logic and view layer strictly separated from the core engine, reducing complexity and accidental coupling.

---

## Theming & Views

Morphine includes a powerful templating system:

- `.tpl.html` files live in theme folders
- You can create:
  - `viewname.tpl.html` (standard)
  - `viewname.iterable.tpl.html` (repeatable items)
  - `viewname.conditional.tpl.html` (conditional views)
  - `viewname.SomeName.tpl.html` (precise override/variant)

View controllers bind your backend data with these templates, using a clearly defined structure for extensibility and reuse.

---

## Security by Default

Morphine is designed with cautious, production-grade defaults:

- No automatic global injection
- Sanitization functions integrated
- CLI prevents weak credentials
- Views and templates do not execute logic
- Strict separation of user input and business logic

This enables you to move faster without compromising the integrity of your applications.

---

## CLI Shell

Morphine ships with a built-in, interactive command-line interface:

```bash
php base/cli/morph.php
```

Available commands:
- `install` — Set up database and configurations securely
- `pack` — (Coming soon) Bundle your project for deployment
- `list` — Show all commands
- `exit` — Close the shell

Each step includes guidance and built-in validation to ensure safety and correctness.

---

## Use Cases

Morphine is ideal for:

- CMS platforms
- Internal dashboards
- Real-time apps (WebSocket-ready)
- Lightweight APIs
- Secure enterprise tools
- Full web application
- Academic and research applications

Its balance of simplicity and power makes it a strong choice for projects that need both clarity and control.

---

## Getting Started

```bash
git clone https://github.com/your-org/morphine
cd morphine/base/cli
php morph.php install
```

After configuration, point your web server to `index.php`.

---

## License

MIT — Free to use, modify, and distribute.

---

## Final Thoughts

Morphine isn’t here to compete on buzzwords. It’s here to simplify PHP development with a codebase that’s **readable**, **modular**, and **secure by design**. If you've ever wanted a framework that **works with you**, not against you — Morphine is what you've been looking for.
