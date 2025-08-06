# 🚀 MarwaPHP Framework

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/php-8.2%2B-orange)
![Build](https://img.shields.io/badge/build-passing-brightgreen)

**MarwaPHP** is a fast, lightweight, and modular PHP framework built for modern web development. It simplifies API and application development with expressive routing, powerful CLI tools, and a clean MVC structure — all while staying minimal and extensible.

---

## 📦 Features

- ✅ Lightweight MVC structure
- 🔧 CLI tools for rapid scaffolding
- 🛠 Schema Builder, Migrations, and Seeders
- 🔐 JWT Authentication & Middleware
- 🌐 Built-in CORS support
- 📅 Task Scheduling & Queues
- 🧰 Helper functions & Utilities
- 🐳 Docker-ready for scalable deployment

---
# Why Another Framework?

I've been developing PHP projects for over 20 years and have extensively worked with frameworks like CodeIgniter. While these frameworks enabled rapid development, I encountered recurring pain points — frequent breaking changes, bloated general-purpose features, and backward-incompatible updates that rendered many of my projects obsolete. Maintaining legacy code became increasingly difficult as frameworks evolved.

Then came Laravel with a modern and elegant syntax, but it introduced its own challenges: too many built-in features, increased complexity, and unnecessary overhead for lightweight projects.

That’s when I decided to build my own framework — MarwaPHP. It's thoughtfully designed to be lightweight, modular, and efficient, using only the best libraries in the PHP ecosystem. MarwaPHP delivers a Laravel-like developer experience without the bloat, making it ideal for scalable, maintainable, and production-ready applications.

---

## 📁 Project Structure

```
marwa-php/
├── app/                → Application Code (Controllers, Models, Views)
├── config/             → Configuration files
├── public/             → Web root (index.php)
├── routes/             → Route definitions
├── resources/          → Views & Assets
├── database/           → Migrations, Seeders
├── tests/              → Unit & Feature tests
└── vendor/             → Composer packages
```

---

## 🚀 Getting Started

```bash
# Clone the repo
git clone https://github.com/memran/marwa-php.git

# Enter project directory
cd marwa-php

# Install dependencies
composer install

# Run dev server
php -S localhost:8000 -t public/
```

---

## 📚 Documentation

Visit the [MarwaPHP GitHub Wiki](https://github.com/memran/marwa-php/wiki) to get started and learn all features:

- [Getting Started](https://github.com/memran/marwa-php/wiki/Getting-Started)
- [Routing](https://github.com/memran/marwa-php/wiki/Routing)
- [Controllers](https://github.com/memran/marwa-php/wiki/Controller)
- [Models](https://github.com/memran/marwa-php/wiki/Model)
- [Views (Twig)](https://github.com/memran/marwa-php/wiki/Twig)
- [Authentication](https://github.com/memran/marwa-php/wiki/Authentication)
- [Authorization (Roles & Policies)](https://github.com/memran/marwa-php/wiki/Authorization)
- [Session Management](https://github.com/memran/marwa-php/wiki/Session)
- [Queue System](https://github.com/memran/marwa-php/wiki/Queue)
- [Task Scheduling](https://github.com/memran/marwa-php/wiki/TaskSchedule)
- [Docker Setup](https://github.com/memran/marwa-php/wiki/Docker-Tutorial)

---

## 💻 Examples

- 📦 Build a RESTful API in minutes
- 🔐 Secure routes with policies
- 🧠 Schedule background tasks
- 📊 Queue jobs with Redis
- 🧪 Run PHPUnit tests

---

## 🤝 Contributing

We welcome contributions! Open an issue, submit a PR, or improve documentation.

---

## 📄 License

MIT © [Mohammad Emran](https://github.com/memran)
