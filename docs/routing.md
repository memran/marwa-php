# 🛣️ Routing Overview — MarwaPHP

MarwaPHP provides a clean, expressive routing system that maps HTTP requests to your application's controllers or closures. It's inspired by Laravel and optimized for microframework speed, making route definitions easy to manage and powerful enough for any modern PHP application.

---

## 🚀 Why Use MarwaPHP Routing?

- Clean separation of URL structure and logic
- Supports RESTful controllers and route groups
- Named routes for flexible linking
- Middleware and parameter constraints supported

---

## 📁 Defining Routes

Routes are typically defined in `routes/web.php` or `routes/api.php`.

### Basic Route

```php
Route::get('/', function () {
    return view('home');
});
```

### Route With Controller

```php
Route::get('/about', 'PageController@about');
```

### POST Route

```php
Route::post('/submit', 'FormController@submit');
```

---

## 🔀 Supported HTTP Verbs

- `Route::get()` — for read/display
- `Route::post()` — for form submission or data creation
- `Route::put()` — for updating resources
- `Route::patch()` — for partial updates
- `Route::delete()` — for deleting resources
- `Route::any()` — matches all HTTP methods
- `Route::match(['GET', 'POST'], '/url', $handler)` — matches selected methods

---

## 🧭 Named Routes

```php
Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
```

Generate URLs dynamically:

```php
$url = route('dashboard');
```

---

## 🧱 Route Parameters

### Required Parameters

```php
Route::get('/user/{id}', 'UserController@show');
```

Accessed in controller method as:

```php
public function show($id)
```

### Optional Parameters

```php
Route::get('/post/{slug?}', 'PostController@view');
```

---

## 🧪 Route Constraints

You can constrain parameters using regular expressions:

```php
Route::get('/user/{id}', 'UserController@show')->where('id', '[0-9]+');
```

---

## 🧰 Middleware Support

Apply middleware to routes:

```php
Route::get('/admin', 'AdminController@index')->middleware('auth');
```

You can also apply middleware to route groups.

---

## 🧩 Route Groups

Group routes with common properties:

```php
Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
    Route::get('/dashboard', 'AdminController@dashboard');
    Route::get('/users', 'UserController@index');
});
```

---

## 📦 Resource Routes (if supported)

Automatically generate CRUD routes for a resource:

```php
Route::resource('posts', 'PostController');
```

This generates routes for:
- index
- create
- store
- show
- edit
- update
- destroy

---

## 🧠 Best Practices

- Use route naming for flexibility and refactoring
- Group routes for better organization
- Keep route closures small — offload logic to controllers
- Apply middleware for access control
- Follow RESTful conventions for API endpoints

---

> 🌐 MarwaPHP's routing system is fast, expressive, and powerful enough for modern web and API development.
