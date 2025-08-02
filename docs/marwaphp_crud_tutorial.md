# 📦 CRUD Tutorial in MarwaPHP

This guide walks you through a simple **CRUD (Create, Read, Update, Delete)** app using MarwaPHP. We'll build a task manager for demonstration.

---

## 🏗 1. Create the Model and Migration

### 📄 Task Model (`App\Models\Task.php`)

```php
namespace App\Models;

use Marwa\MVC\Model\Model;

class Task extends Model
{
    protected $table = 'tasks';
    protected $fillable = ['title', 'description'];
}
```

### 📂 Migration

```php
Builder::create('tasks', function($table) {
    $table->id();
    $table->string('title');
    $table->text('description');
    $table->timestamps();
});
```

Run the migration:

```bash
php cli migrate
```

---

## 📥 2. Create Routes

```php
Route::get('/tasks', 'TaskController@index');
Route::get('/tasks/create', 'TaskController@create');
Route::post('/tasks', 'TaskController@store');
Route::get('/tasks/{id}/edit', 'TaskController@edit');
Route::post('/tasks/{id}/update', 'TaskController@update');
Route::post('/tasks/{id}/delete', 'TaskController@destroy');
```

---

## 🧠 3. Controller (`App\Controllers\TaskController.php`)

```php
namespace App\Controllers;

use App\Models\Task;
use Marwa\MVC\Request\Request;

class TaskController
{
    public function index()
    {
        $tasks = Task::all();
        return view('tasks/index.twig', ['tasks' => $tasks]);
    }

    public function create()
    {
        return view('tasks/create.twig');
    }

    public function store(Request $request)
    {
        Task::create($request->only(['title', 'description']));
        return redirect('/tasks');
    }

    public function edit($id)
    {
        $task = Task::find($id);
        return view('tasks/edit.twig', ['task' => $task]);
    }

    public function update($id, Request $request)
    {
        $task = Task::find($id);
        $task->update($request->only(['title', 'description']));
        return redirect('/tasks');
    }

    public function destroy($id)
    {
        Task::destroy($id);
        return redirect('/tasks');
    }
}
```

---

## 🎨 4. Views (Twig)

### `resources/views/tasks/index.twig`

```twig
<h1>Tasks</h1>
<a href="/tasks/create">Add Task</a>
<ul>
{% for task in tasks %}
    <li>{{ task.title }} - 
        <a href="/tasks/{{ task.id }}/edit">Edit</a>
        <form action="/tasks/{{ task.id }}/delete" method="post" style="display:inline;">
            <button type="submit">Delete</button>
        </form>
    </li>
{% endfor %}
</ul>
```

### `resources/views/tasks/create.twig`

```twig
<h1>Create Task</h1>
<form action="/tasks" method="post">
    <input name="title" placeholder="Title">
    <textarea name="description"></textarea>
    <button type="submit">Save</button>
</form>
```

### `resources/views/tasks/edit.twig`

```twig
<h1>Edit Task</h1>
<form action="/tasks/{{ task.id }}/update" method="post">
    <input name="title" value="{{ task.title }}">
    <textarea name="description">{{ task.description }}</textarea>
    <button type="submit">Update</button>
</form>
```

---

## ✅ Done!

You now have a full CRUD system using MarwaPHP. You can add auth middleware, validation, and flash messages to enhance it further.

