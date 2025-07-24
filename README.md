<p align="center">
  <img src="https://github.com/user-attachments/assets/5e5614a4-70a2-40f1-8818-1c7123f5120d" width="300" alt="Morphine Logo">
</p>

<h1 align="center">Morphine PHP Framework</h1>

<p align="center"><em>
A modern, secure, and transparent PHP 8+ framework built on the MORE architecture — for developers who demand clarity, structure, and full control.
</em></p>
<p align="center">
  <a href="https://doi.org/10.5281/zenodo.15977823"><img src="https://zenodo.org/badge/DOI/10.5281/zenodo.15977823.svg" alt="DOI"></a>
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License: MIT"></a>
  <img src="https://img.shields.io/github/stars/0x31337/morphine?style=social" alt="GitHub stars">
  <img src="https://img.shields.io/github/issues/0x31337/morphine" alt="GitHub issues">
</p>

---

## 🚀 What is Morphine?

**Morphine** is a next-generation PHP framework designed for professional and academic use. It is:
- **Strictly PSR-1/PSR-4/PSR-12 compliant**
- **Composer-native** (no custom autoloaders)
- **PHP 8+ only** (typed properties, modern syntax)
- **Security-first** (no magic, no globals, no dynamic properties)
- **Built on the MORE architecture** (Models, Operations, Renders, Events)
- **CLI-powered** for rapid project setup, packaging, and diagnostics

Morphine is ideal for building secure web apps, internal tools, dashboards, APIs, and educational projects where code clarity and maintainability are paramount.

---

## 🧱 Core Principles

- **Simplicity & Transparency**: No hidden magic, no vendor lock-in, no bloat. What you see is what you get.
- **Security by Default**: Strict validation, prepared statements, and clear boundaries at every layer.
- **Predictable Architecture**: The MORE model enforces a logical separation of concerns.
- **Modern PHP**: Leverages namespaces, strict types, static analysis, and Composer autoloading.
- **CLI-Driven**: Powerful command-line tools for installation, packaging, and code generation.

---

## 🧠 The MORE Architecture

Morphine implements the **MORE** pattern:

| Layer         | Responsibility                            |
|---------------|--------------------------------------------|
| **Models**     | Typed, read-only access to data sources    |
| **Operations** | Write logic and side-effect isolation      |
| **Renders**    | Bind data to views, no logic in HTML       |
| **Events**     | Routing, HTTP flow, and dispatch           |

> 📖 [Read the MORE Whitepaper (PDF)](https://github.com/user-attachments/files/21241161/whitepaper.pdf)

---

## ⚡️ Quick Start

### Requirements
- PHP 8.0 or higher
- Composer
- MySQL (or compatible)

### Installation

```bash
# 1. Clone the repository
$ git clone https://github.com/0x31337/morphine.git
$ cd morphine

# 2. Install dependencies
$ composer install

# 3. Run the Morphine CLI installer
$ php Base/CLI/morph.php install
```

### Directory Structure
```
Morphine/
├── Application/
│   ├── Models/
│   ├── Operations/
│   ├── Views/
│   └── ...
├── Base/
│   ├── Engine/
│   ├── Events/
│   ├── Renders/
│   ├── CLI/
│   └── ...
├── index.php
├── composer.json
└── ...
```

---

## 🛠 Features
- **PSR-4 Autoloading** via Composer
- **Strict PHP 8+**: No legacy code, no dynamic properties
- **Secure Database Layer**: Prepared statements, identifier sanitization, no SQL injection
- **Powerful CLI**: Install, package, create views/models/operations, inspect channels, and more
- **Theme & Template System**: Clean separation of logic and presentation
- **Extensible**: Add your own utilities in `Base/Misc` with PSR-4 support
- **Comprehensive Documentation**: [Morphine Wiki](https://github.com/0x31337/morphine/wiki)

---

## 📚 Documentation & Resources
- [Full Documentation (Wiki)](https://github.com/0x31337/morphine/wiki)
- [MORE Architecture Whitepaper (PDF)](https://github.com/user-attachments/files/21241161/whitepaper.pdf)
- [CLI Usage Guide](https://github.com/0x31337/morphine/wiki/16.-CLI)

---

## 🤝 Contributing

Contributions are welcome! Please:
- Follow PSR-12 coding standards
- Use namespaces and Composer autoloading
- Write clear, documented code
- Submit pull requests with a clear description

---

## 📄 License

Released under the [MIT License](https://opensource.org/licenses/MIT).

---

## 💬 Final Thoughts

Morphine is not just another PHP framework. It is a **purpose-built tool for clarity, security, and long-term maintainability**. If you value readable, modern, and robust PHP — Morphine is for you.

---

<p align="center"><b>Build with confidence. Build with Morphine.</b></p>
