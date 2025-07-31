# 📣 Event System — MarwaPHP

MarwaPHP includes a robust event broadcasting and listening system designed to decouple your application logic and encourage clean, modular architecture. The event system follows the publisher-subscriber pattern and allows you to dispatch custom events and handle them asynchronously or synchronously.

---

## 🔄 Why Use Events?

- Decouple business logic and side effects
- Trigger background processes like notifications, logs, etc.
- Enable modular and reusable code
- Facilitate domain-driven design (DDD)

---

## 📦 Event Anatomy

An event in MarwaPHP is a simple class that contains relevant data. It can be dispatched globally and handled by one or more listeners.

---

## 🧱 Creating Events

Use the CLI to generate an event:

```bash
php marwa make:event UserRegistered
```

Example:

```php
class UserRegistered
{
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}
```

---

## 🎧 Creating Listeners

Create a listener that will handle the event:

```bash
php marwa make:listener SendWelcomeEmail
```

Example:

```php
class SendWelcomeEmail
{
    public function handle(UserRegistered $event)
    {
        Mail::to($event->user->email)->send('emails.welcome', $event->user);
    }
}
```

---

## 🔁 Registering Event Listeners

You can register your events and listeners in `config/events.php` or an EventServiceProvider:

```php
return [
    UserRegistered::class => [
        SendWelcomeEmail::class,
        LogNewUser::class,
    ],
];
```

---

## 🚀 Dispatching Events

Use the `Event` facade to dispatch:

```php
Event::fire(new UserRegistered($user));
```

You can also dispatch using helper:

```php
event(new UserRegistered($user));
```

---

## ⏱ Queued Listeners (Planned Feature)

In future versions, listeners may implement `ShouldQueue` interface to be queued automatically:

```php
class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserRegistered $event) { ... }
}
```

---

## ✅ Best Practices

- Keep event classes simple — just carry data
- Listeners contain the logic that responds to the event
- One event can have many listeners
- Avoid putting business logic directly in controllers
- Prefer broadcasting domain-specific events like `InvoicePaid`, `OrderShipped`, etc.

---

## 🧠 Benefits

- Improves maintainability and modularity
- Simplifies testing and debugging
- Enables event-driven or reactive system design

---

> 📬 The Event system in MarwaPHP helps you build powerful, loosely-coupled applications by cleanly separating responsibilities.
