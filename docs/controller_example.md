# 🧭 Controller Example — MarwaPHP

In MarwaPHP, controllers help organize your application logic into clean, reusable classes. They act as the intermediary between routes and business logic. Controllers allow you to group related request-handling logic and follow the MVC (Model-View-Controller) architecture pattern.

---

## 🏗 Creating a Controller

Use the CLI to generate a controller:

```bash
php marwa make:controller UserController
```

This creates a controller in `app/Http/Controllers/UserController.php`.

---

## 📄 Basic Controller Example

```php
namespace App\Http\Controllers;

use App\Models\User;

class UserController
{
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store()
    {
        $data = request()->all();
        User::create($data);
        return redirect('/users');
    }

    public function edit($id)
    {
        $user = User::find($id);
        return view('users.edit', compact('user'));
    }

    public function update($id)
    {
        $user = User::find($id);
        $user->update(request()->all());
        return redirect('/users/' . $id);
    }

    public function destroy($id)
    {
        User::destroy($id);
        return redirect('/users');
    }
}
```

---

## 🗂 Route Setup

Use `Route::resource()` to register all RESTful routes:

```php
Route::resource('users', 'UserController');
```

This will generate routes for:

| HTTP Method | URL             | Controller Method |
|-------------|------------------|-------------------|
| GET         | /users          | index             |
| GET         | /users/create   | create            |
| POST        | /users          | store             |
| GET         | /users/{id}     | show              |
| GET         | /users/{id}/edit| edit              |
| PUT/PATCH   | /users/{id}     | update            |
| DELETE      | /users/{id}     | destroy           |

---

## 🧪 Tips for Writing Controllers

- Keep methods short and focused
- Avoid direct SQL — use models or services
- Use `request()` helper for input data
- Redirect or return view after completing logic
- Consider using Form Request classes for validation (if supported)

---

## 💡 Best Practices

- Use one controller per resource/domain
- Inject dependencies via constructor if supported
- Return JSON for API controllers and views for web
- Avoid business logic in controllers — move to services/models

---

> 🚦 Controllers are the traffic controllers of your app — they connect the route to logic in a structured and maintainable way.
