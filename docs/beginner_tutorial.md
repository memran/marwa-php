# 🌟 MarwaPHP Beginner's Guide — Step-by-Step Tutorial

Welcome to the MarwaPHP Framework! This guide will walk you through building your first web application using MarwaPHP. Whether you're familiar with Laravel or new to PHP frameworks, this tutorial is perfect for learning the basics.

---

## 📦 Step 1: Install MarwaPHP

Make sure you have PHP 8.0+ and Composer installed.

```bash
composer create-project memran/marwa-php myapp
cd myapp
php marwa serve
```

Your app will be running on `http://localhost:8000`.

---

## ⚙️ Step 2: Configure Your Application

Edit the `.env` file:

```env
APP_NAME=MarwaApp
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marwa_db
DB_USERNAME=root
DB_PASSWORD=
```

Create your database `marwa_db` in MySQL.

---

## 📂 Step 3: Understand Directory Structure

```text
app/                → Application logic (Controllers, Models)
routes/             → Web/API route files
resources/views/    → Blade-style views
config/             → Config files
database/           → Migrations, Seeders
public/             → Public assets (index.php, JS, CSS)
```

---

## 🧭 Step 4: Define a Route

Edit `routes/web.php`:

```php
Route::get('/', function () {
    return view('welcome', ['message' => 'Hello from MarwaPHP!']);
});
```

Create `resources/views/welcome.php`:

```php
<!DOCTYPE html>
<html>
<head><title>Welcome</title></head>
<body>
    <h1><?= $message ?></h1>
</body>
</html>
```

---

## 🎮 Step 5: Create a Controller

```bash
php marwa make:controller PageController
```

Edit `app/Http/Controllers/PageController.php`:

```php
class PageController
{
    public function about()
    {
        return view('about', ['title' => 'About Us']);
    }
}
```

Update `routes/web.php`:

```php
Route::get('/about', 'PageController@about');
```

Create `resources/views/about.php`.

---

## 🧬 Step 6: Create a Model and Migration

```bash
php marwa make:model Post
php marwa make:migration create_posts_table
```

Edit `database/migrations/...create_posts_table.php`:

```php
public function up()
{
    Builder::create('posts', function($table) {
        $table->id();
        $table->string('title');
        $table->text('body');
        $table->timestamps();
    });
}
```

Run migration:

```bash
php marwa migrate
```

---

## ✍️ Step 7: Insert and Display Data

Edit `PageController.php`:

```php
use App\Models\Post;

public function blog()
{
    $posts = Post::all();
    return view('blog', compact('posts'));
}
```

Update routes:

```php
Route::get('/blog', 'PageController@blog');
```

Create `resources/views/blog.php` to loop through `$posts`.

---

## 📨 Step 8: Form Handling

In `routes/web.php`:

```php
Route::post('/contact', 'FormController@submit');
```

Create `FormController`, process `Input::get('email')`, and validate data.

---

## 🧼 Step 9: Add Middleware (Optional)

You can apply middleware like `auth`, `csrf`, `admin` to your routes or controllers.

```php
Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', 'UserController@dashboard');
});
```

---

## ✅ Step 10: Deploy to Production

- Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- Use Apache/Nginx to point to `public/` directory
- Use Composer to autoload
- Run migrations and seeders on live DB

---

## 🎉 You’re Done!

You've just built your first dynamic MarwaPHP application!

Happy Coding!
