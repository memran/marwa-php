# 👤 User Registration, Email Verification, and Profile Management in MarwaPHP

This tutorial walks you through a complete flow:
1. User registration
2. Email verification
3. Account activation
4. Login
5. Dashboard & Profile management (CRUD)
6. Twig view integration

---

## 🧱 Prerequisites

- MarwaPHP installed
- Mail config (SMTP) setup in `config/mail.php`
- Twig engine enabled and set as default

---

## 📝 Step 1: User Registration Route & Controller

### Route

```php
Route::get('/register', 'AuthController@registerForm');
Route::post('/register', 'AuthController@register');
```

### AuthController.php

```php
public function registerForm()
{
    return view('auth/register.twig');
}

public function register(Request $request)
{
    $data = $request->only(['name', 'email', 'password']);
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    $data['is_verified'] = false;
    $data['verify_token'] = bin2hex(random_bytes(16));

    $user = User::create($data);

    Mail::to($data['email'])->send(new VerifyEmail($user));

    return redirect('/login')->with('success', 'Check your email for verification.');
}
```

---

## 📧 Step 2: Email Verification

### Route

```php
Route::get('/verify/{token}', 'AuthController@verify');
```

### Controller

```php
public function verify($token)
{
    $user = User::where('verify_token', $token)->first();
    if ($user) {
        $user->is_verified = true;
        $user->verify_token = null;
        $user->save();
        return redirect('/login')->with('success', 'Your account is now active.');
    }
    return response(['error' => 'Invalid token'], 400);
}
```

---

## 🔐 Step 3: Login Logic

```php
public function login(Request $request)
{
    $user = User::where('email', $request->email)->first();
    if ($user && password_verify($request->password, $user->password)) {
        if (!$user->is_verified) {
            return back()->with('error', 'Please verify your email first.');
        }
        session()->set('user', $user);
        return redirect('/dashboard');
    }
    return back()->with('error', 'Invalid credentials.');
}
```

---

## 📊 Step 4: Dashboard

### Route

```php
Route::get('/dashboard', 'UserController@dashboard')->middleware('auth');
```

### Controller

```php
public function dashboard()
{
    $user = auth()->user();
    return view('user/dashboard.twig', ['user' => $user]);
}
```

---

## 👤 Step 5: Profile CRUD

### Routes

```php
Route::get('/profile', 'UserController@profile');
Route::post('/profile/update', 'UserController@update');
Route::post('/profile/delete', 'UserController@delete');
```

### Controller

```php
public function profile()
{
    return view('user/profile.twig', ['user' => auth()->user()]);
}

public function update(Request $request)
{
    $user = auth()->user();
    $user->name = $request->input('name');
    $user->save();
    return back()->with('success', 'Profile updated');
}

public function delete()
{
    $user = auth()->user();
    $user->delete();
    session()->destroy();
    return redirect('/register');
}
```

---

## 🌐 Step 6: Twig Templates

### `auth/register.twig`

```twig
<form method="POST" action="/register">
  <input type="text" name="name" placeholder="Name" required>
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Password" required>
  <button type="submit">Register</button>
</form>
```

### `user/dashboard.twig`

```twig
<h1>Welcome, {{ user.name }}</h1>
<a href="/profile">View Profile</a>
```

### `user/profile.twig`

```twig
<form method="POST" action="/profile/update">
  <input type="text" name="name" value="{{ user.name }}">
  <button type="submit">Update</button>
</form>
<form method="POST" action="/profile/delete">
  <button type="submit" onclick="return confirm('Delete account?')">Delete Account</button>
</form>
```

---

## ✅ Final Tips

- Always hash passwords
- Store verification tokens securely
- Use Twig for safe and elegant frontend rendering
- Validate all inputs

---

🎉 Your MarwaPHP app now supports registration, email verification, secure login, and profile management!
