# ⏰ Task Scheduling — MarwaPHP

MarwaPHP includes a lightweight but expressive task scheduling system, allowing developers to schedule repetitive jobs using cron-like expressions in PHP. It eliminates the need to define each task separately in the system's crontab and offers fine-grained control over task execution.

---

## 🚀 Why Use Task Scheduling?

- Automate periodic jobs (e.g., backups, cleanups, reports)
- Centralize scheduling logic in PHP
- Avoid crontab clutter and manual edits
- Add conditional and dynamic execution logic

---

## 📁 Task File Location

All scheduled tasks are defined in `app/Console/Kernel.php` or `tasks/schedule.php` (depending on implementation). This file acts as the scheduler entry point.

---

## 📝 Defining Scheduled Tasks

You can define scheduled tasks using the `Schedule` facade or injected scheduler:

```php
$schedule->command('cleanup:logs')->dailyAt('03:00');
$schedule->job(new SendNewsletter)->weekly();
$schedule->call(function () {
    DB::table('temp')->delete();
})->everyMinute();
```

---

## ⏱ Available Scheduling Methods

| Method              | Description                      |
|---------------------|----------------------------------|
| `everyMinute()`     | Run every minute                 |
| `everyFiveMinutes()`| Run every 5 minutes              |
| `hourly()`          | Run every hour                   |
| `daily()`           | Run once daily at midnight       |
| `dailyAt('13:00')`  | Run daily at specific time       |
| `weekly()`          | Run every Sunday at midnight     |
| `monthly()`         | Run on the 1st of each month     |
| `cron('* * * * *')` | Run using full cron expression   |

---

## ✅ Command Types Supported

- Artisan-style commands (registered CLI commands)
- Invokable job classes
- Closures / inline functions
- Shell commands (via `->exec('bash command')`)

---

## 🧪 Conditional Execution

You can conditionally run tasks:

```php
$schedule->command('backup:run')->daily()->when(function () {
    return env('APP_ENV') === 'production';
});
```

---

## 🧵 Overlapping Protection

Prevent overlapping task instances:

```php
$schedule->command('emails:send')->withoutOverlapping();
```

This ensures a task won't start if the previous instance hasn't finished.

---

## 🖥 System Cron Setup

To trigger MarwaPHP’s scheduler, add this line to your system crontab:

```bash
* * * * * php /path/to/your/project marwa schedule:run >> /dev/null 2>&1
```

This runs the scheduler every minute and lets PHP determine what needs to run.

---

## 🧠 Best Practices

- Keep all scheduled jobs in one place for visibility
- Use named commands and queues to decouple logic
- Use logging or notifications for job monitoring
- Test time-based logic locally with short intervals

---

> 🧭 MarwaPHP’s task scheduler lets you automate jobs elegantly using PHP syntax without touching the system crontab.
