# 🖼️ View System — MarwaPHP

MarwaPHP includes a simple yet flexible view rendering engine based on native PHP templates. It allows developers to organize HTML output separately from application logic, keeping the codebase clean and maintainable.

---

## 🎯 Why Use the View System?

- Clean separation of logic and presentation
- Reusable components and layouts
- Template inheritance with partials
- Easy integration with frontend frameworks

---

## 📁 View Directory Structure

Views are typically stored in the `resources/views/` directory:

```text
resources/views/
├── layouts/
│   └── app.php
├── partials/
│   └── header.php
├── home.php
└── about.php
```

---

## 📄 Rendering Views

Use the `view()` helper or `View` facade:

```php
return view('home');
```

With data:

```php
return view('home', ['title' => 'Welcome', 'user' => $user]);
```

This will load the file at `resources/views/home.php`.

---

## 🔧 Passing Data to Views

Data can be passed as associative arrays:

```php
view('profile', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

---

## 🧱 Template Inheritance

You can include other view files using `include` or custom helpers:

### `layouts/app.php`

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'MarwaPHP' ?></title>
</head>
<body>
    <?php include 'partials/header.php'; ?>
    <?= $content ?? '' ?>
</body>
</html>
```

### `home.php`

```php
<?php $title = "Home Page"; ob_start(); ?>

<h1>Welcome to MarwaPHP</h1>

<?php $content = ob_get_clean(); include 'layouts/app.php'; ?>
```

---

## 📦 View Caching (Optional)

If your application renders large views frequently, caching strategies may be added (in future or custom extensions). You may implement your own view caching using filesystem or opcode cache.

---

## 📑 Reusable Partials

Extract repeated HTML (headers, footers, navbars) into partials:

```php
<?php include 'partials/header.php'; ?>
```

This improves reusability and maintainability.

---

## 🔐 Escaping Output

Use `htmlspecialchars()` for user-generated content:

```php
<?= htmlspecialchars($user['name']) ?>
```

Or use custom blade-like helpers if implemented.

---

## ✅ Best Practices

- Use layouts and partials to DRY your HTML
- Keep logic out of views — pass only data
- Use view helpers for URLs, assets, CSRF tokens, etc.
- Escape all dynamic output to prevent XSS

---

> 🧩 MarwaPHP’s view system is simple, fast, and perfect for developers who prefer control over abstraction.
