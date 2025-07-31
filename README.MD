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

## 🐳 Run with Docker

```bash
# Development environment
./launch.sh dev

# Production environment
./launch.sh prod
```

---

## 📚 Documentation

Explore full guides, tutorials, and API references:

- [Getting Started](docs/getting_started.md)
- [JWT Authentication](docs/jwt_authentication_tutorial.md)
- [User Roles & Permissions](docs/user_roles_policy_tutorial.md)
- [Docker Setup](docs/docker_tutorial.md)

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
