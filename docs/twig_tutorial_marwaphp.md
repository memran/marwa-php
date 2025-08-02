
# 🧵 Using Twig in MarwaPHP

Twig is a modern template engine for PHP — clean, fast, and secure. MarwaPHP comes with **built-in Twig integration** to power your views with logic-free templates.

---

## 📦 1. Installation

Twig is preinstalled in MarwaPHP. If not, run:

```bash
composer require twig/twig
```

---

## 🛠 2. Basic Usage

Create a view: `resources/views/welcome.twig`

```twig
<h1>Hello, {{ name }}!</h1>
```

In your controller:

```php
return view('welcome.twig', ['name' => 'MarwaPHP']);
```

---

## 🔁 3. Twig Features

### ✅ Variables

```twig
{{ name }}
{{ user.email }}
```

### ✅ Control Structures

```twig
{% if user %}
  Hello, {{ user.name }}
{% else %}
  Please login.
{% endif %}

{% for post in posts %}
  <li>{{ post.title }}</li>
{% endfor %}
```

### ✅ Filters

```twig
{{ name|upper }}
{{ date|date("Y-m-d") }}
```

---

## 📂 4. Template Inheritance

### `base.twig`

```twig
<!DOCTYPE html>
<html>
<head><title>{% block title %}My Site{% endblock %}</title></head>
<body>
  <header>{% block header %}Header{% endblock %}</header>
  <main>{% block content %}{% endblock %}</main>
</body>
</html>
```

### `home.twig`

```twig
{% extends "base.twig" %}

{% block title %}Home Page{% endblock %}

{% block content %}
  <h1>Welcome to MarwaPHP!</h1>
{% endblock %}
```

---

## 🧩 5. Using Twig Extensions

MarwaPHP allows you to register custom Twig extensions.

### Example: Custom Filter

```php
use Twig\TwigFilter;

$twig->addFilter(new TwigFilter('reverse', function ($text) {
    return strrev($text);
}));
```

In a service provider or view boot script:

```php
\Marwa\MVC\View\TwigView::register(function($twig) {
    $twig->addFilter(new \Twig\TwigFilter('slug', function($text) {
        return strtolower(str_replace(' ', '-', $text));
    }));
});
```

Then in Twig:

```twig
{{ "Hello World"|slug }}  {# hello-world #}
```

---

## ⚙️ 6. Global Variables

```php
\Marwa\MVC\View\TwigView::register(function($twig) {
    $twig->addGlobal('app_name', 'MarwaPHP');
});
```

In template:

```twig
<p>{{ app_name }}</p>
```

---

## 📑 7. Loading Partials

```twig
{% include 'partials/header.twig' %}
```

---

## ✅ Summary

Twig + MarwaPHP provides:
- Clean and secure templates
- Inheritance and reusability
- Custom extensions and filters
- Easy integration with MarwaPHP controllers
