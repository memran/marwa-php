# Skill: marwa-scheduler-task

Goal:
Create scheduled tasks for a module.

Use With:

- marwa-framework
- marwa-module-author

Task Types:

- command
- queue
- call

Rules:

- Tasks must remain thin.
- Business logic belongs elsewhere.
- Register tasks through manifest.php.
- Use framework scheduler.
- Support database-backed scheduler status.
- Use withoutOverlapping when appropriate.

Manifest Example:

'tasks' => [
'cleanup_exports' => [
'type' => 'command',
'command' => 'reports:cleanup',
'schedule' => [
'everySeconds' => 300
],
'withoutOverlapping' => true,
],
]

Task Checklist:

- task name
- task type
- schedule
- overlap handling
- logging
- failure handling

Required Output:

1. Task definition
2. Manifest update
3. Command/job implementation
4. Schedule configuration
5. Monitoring approach
6. Tests needed
