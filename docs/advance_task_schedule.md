# 🕒 Advanced Queue Task Scheduling in MarwaPHP

MarwaPHP offers a powerful task scheduling and queue system similar to Laravel’s, allowing you to execute background jobs and periodic tasks seamlessly using Redis or file-based queues.

This guide walks you through everything from setting up tasks to advanced features like job delay, retry, and cron automation.

---

## 🧱 Core Concepts

| Concept       | Description |
|---------------|-------------|
| `ShouldQueue` | Interface used to push a class to the queue |
| `TaskScheduler` | Central engine for managing scheduled tasks |
| `schedule:run` | CLI command to trigger due jobs |
| `queue:work`   | CLI command to process background jobs |

---

## 📦 Step 1: Create a Scheduled Job

### Example: `SendDailyReportTask`

```php
namespace App\Tasks;

use App\Queue\ShouldQueue;

class SendDailyReportTask implements ShouldQueue
{
    public function handle()
    {
        // Your business logic
        log("✅ Daily report sent at: " . date('Y-m-d H:i:s'));
    }
}
```

> ✅ `handle()` is the execution point of any scheduled task or queued job.

---

## 🧾 Step 2: Configure Task Schedule

### In `config/schedule.php`

```php
use App\Tasks\SendDailyReportTask;
use App\Tasks\CleanupTempFilesTask;
use App\Tasks\WeeklyEmailSummaryTask;

return [
    'daily' => [
        SendDailyReportTask::class
    ],
    'weekly' => [
        WeeklyEmailSummaryTask::class
    ],
    'everyMinute' => [
        CleanupTempFilesTask::class
    ],
];
```

---

## 🧪 Step 3: Execute the Scheduler

Run manually or via cron:

```bash
php cli schedule:run
```

Set it in your system’s crontab to run every minute:

```bash
* * * * * cd /var/www/marwa-php && php cli schedule:run >> /dev/null 2>&1
```

---

## ⚙️ Step 4: Enable Queue Worker

The scheduler only **queues** tasks; to execute them, a worker must be active:

```bash
php cli queue:work
```

Keep it running with a process manager (e.g. `supervisord`, `pm2`, `systemd`) in production.

---

## ⏳ Step 5: Job Delay, Retry, and Timeout (Optional)

Extend the job to control timing behavior:

```php
class DelayedTask implements ShouldQueue
{
    public $delay = 60; // Delay by 60 seconds
    public $retryAfter = 120; // Retry after 2 minutes
    public $timeout = 300; // Fail if job runs over 5 mins

    public function handle()
    {
        log("🕒 Delayed task executed.");
    }
}
```

---

## 🔄 Dynamic Runtime Scheduling

You can also schedule tasks programmatically:

```php
use Marwa\App\Task\TaskScheduler;

TaskScheduler::everyMinute(MyDynamicTask::class);
TaskScheduler::daily(MyAnotherTask::class);
```

Use this in `routes/console.php` or custom command files.

---

## 🧹 Sample Cleanup Task

```php
class CleanupTempFilesTask implements ShouldQueue
{
    public function handle()
    {
        $dir = '/tmp/marwa/';
        foreach (glob($dir . '*') as $file) {
            unlink($file);
        }
        log("🧹 Temp files cleaned.");
    }
}
```

---

## 🔍 Monitoring Suggestions

- Log to file or DB using custom Logger
- Track failed jobs in DB or log file
- Use `queue:failed` handler for error response

---

## 📈 Best Practices

| Practice | Benefit |
|----------|---------|
| Use queues for heavy/slow tasks | Improve user experience |
| Always log task start/end | Debug & auditability |
| Use `ShouldQueue` on all jobs | Async performance |
| Use cron to automate `schedule:run` | Full automation |
| Monitor worker health | Avoid silent failure |

---

## ✅ Summary

| Command                | Purpose                        |
|------------------------|--------------------------------|
| `php cli schedule:run` | Queues all due tasks           |
| `php cli queue:work`   | Processes queued jobs          |
| `ShouldQueue`          | Interface for background jobs  |
| `config/schedule.php`  | Define recurring job schedules |

---

🎉 You now have an advanced, production-ready task scheduling system powered by queues in MarwaPHP.

