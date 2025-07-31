# ⚙️ Event & Queue Based User Authentication Flow in MarwaPHP

Enhance your registration process with **event-driven** and **queue-based** architecture in MarwaPHP. This tutorial covers:

- ✅ User registration
- 📩 Email verification via queued event
- 🔐 Login logic
- 🧾 Profile management
- ✨ Twig integration

---

## 🧱 Step 1: Define Event – `UserRegisteredEvent`

Create: `app/Events/UserRegisteredEvent.php`

```php
namespace App\Events;

use App\Models\User;

class UserRegisteredEvent
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
```

---

## 📬 Step 2: Define Listener – `SendVerificationEmailListener`

Create: `app/Listeners/SendVerificationEmailListener.php`

```php
namespace App\Listeners;

use App\Events\UserRegisteredEvent;
use App\Mails\VerifyEmail;
use Mail;

class SendVerificationEmailListener
{
    public function handle(UserRegisteredEvent $event)
    {
        $user = $event->user;
        Mail::to($user->email)->send(new VerifyEmail($user));
    }
}
```

---

## 📂 Step 3: Register Events

In `config/events.php`:

```php
return [
    App\Events\UserRegisteredEvent::class => [
        App\Listeners\SendVerificationEmailListener::class,
    ],
];
```

---

## 🧵 Step 4: Queue Listener for Email

Enable queueing in `SendVerificationEmailListener.php`:

```php
use App\Queue\ShouldQueue;

class SendVerificationEmailListener implements ShouldQueue
{
    public function handle(UserRegisteredEvent $event)
    {
        // Send email logic
    }
}
```

Start queue worker:

```bash
php cli queue:work
```

---

## 📝 Step 5: Trigger Event in Registration

In `AuthController@register`:

```php
public function register(Request $request)
{
    $data = $request->only(['name', 'email', 'password']);
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    $data['is_verified'] = false;
    $data['verify_token'] = bin2hex(random_bytes(16));

    $user = User::create($data);

    event(new \App\Events\UserRegisteredEvent($user));

    return redirect('/login')->with('success', 'Check your email to verify.');
}
```

---

## 🔐 Login After Verification

```php
public function login(Request $request)
{
    $user = User::where('email', $request->email)->first();
    if ($user && password_verify($request->password, $user->password)) {
        if (!$user->is_verified) {
            return back()->with('error', 'Email not verified.');
        }
        session()->set('user', $user);
        return redirect('/dashboard');
    }
    return back()->with('error', 'Invalid credentials.');
}
```

---

## 🧑 Profile Management (Same as before)

- View profile: `/profile`
- Update profile: `/profile/update`
- Delete user: `/profile/delete`

---

## 🌐 Twig Templates

- `register.twig` — registration form
- `dashboard.twig` — user welcome page
- `profile.twig` — view/update/delete profile

(See previous tutorial for full templates.)

---

## 🧠 Summary

| Feature       | How It Works                   |
|---------------|--------------------------------|
| Registration  | User data saved, event fired   |
| Email Verify  | Event listener queues email    |
| Queue Worker  | Background job sends email     |
| Login         | Validates credentials + verify |
| Profile CRUD  | User can update/delete data    |

---

🎉 With event-based + queue-powered flow, your app is more scalable and production-ready!

