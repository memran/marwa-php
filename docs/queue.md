# 📨 Queue System — MarwaPHP

MarwaPHP includes a minimal yet powerful queue system designed to handle background jobs asynchronously. Inspired by Laravel but optimized for micro-framework speed, the queue system lets you offload time-consuming tasks like email delivery, notifications, and report generation.

---

## ⚡ Why Use Queues?

- Improve response time by deferring slow processes
- Decouple job logic from HTTP requests
- Scale workers independently
- Enable real-time processing (emails, logs, alerts)

---

## ⚙️ Configuration

Queue settings are defined in `config/queue.php`.

Common settings include:

```php
return [
    'default' => 'database',

    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
        ],
        'sync' => [
            'driver' => 'sync',
        ],
    ],
];
```

Available drivers:
- `sync`: immediate execution (for local/dev)
- `database`: stores jobs in a DB table (recommended for production)
- (future: redis, beanstalk, sqs)

---

## 🛠 Creating Jobs

Use the CLI to generate a new job:

```bash
php marwa make:job SendWelcomeEmail
```

This creates a file in `app/Jobs/SendWelcomeEmail.php`:

```php
class SendWelcomeEmail
{
    public function handle()
    {
        // your background logic here
    }
}
```

---

## 📬 Dispatching Jobs

Dispatch a job using:

```php
Queue::push(SendWelcomeEmail::class, [$user]);
```

Or delay execution:

```php
Queue::later(60, SendWelcomeEmail::class, [$user]); // after 60 seconds
```

You can also pass closures:

```php
Queue::push(function () {
    // anonymous job logic
});
```

---

## 👷 Running the Queue Worker

Start a queue worker with:

```bash
php marwa queue:work
```

This listens for new jobs and processes them.

Add `--sleep=3` to pause between checks, or `--timeout=60` to set max job time.

---

## 🧹 Managing Failed Jobs

If enabled, failed jobs are stored in the `failed_jobs` table.

Retry failed jobs:

```bash
php marwa queue:retry all
```

Or a specific ID:

```bash
php marwa queue:retry 5
```

---

## 🧪 Best Practices

- Always validate payloads before dispatch
- Use database transactions if your job touches multiple tables
- Handle failures gracefully inside your job's `handle()` method
- Use job batching or chaining for dependent logic
- Scale queue workers horizontally under load

---

## ✅ Example Job

```php
class SendWelcomeEmail
{
    public function handle($user)
    {
        Mail::to($user['email'])->send('emails.welcome', $user);
    }
}
```

Dispatch it:

```php
Queue::push(SendWelcomeEmail::class, [$user]);
```

---

> 🚀 MarwaPHP’s queue system keeps your app responsive while background jobs work silently behind the scenes.
