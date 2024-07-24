<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Subtask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $tasks = Task::where('user_id', $user->id)->with('subtasks')->get();

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'label' => 'nullable|string',
            'priority' => 'nullable|string',
            'due_date' => 'nullable|date',
            'reminder' => 'nullable|date',
            'subtasks' => 'nullable|array',  // Subtasks should be an array if provided
            'subtasks.*.title' => 'required|string|max:255',  // Validation for each subtask title
        ]);

        $user = Auth::user();

        // Create task
        $task = new Task([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'category' => $validatedData['category'],
            'label' => $validatedData['label'],
            'priority' => $validatedData['priority'],
            'due_date' => $validatedData['due_date'],
            'reminder' => $validatedData['reminder'],
            'user_id' => $user->id,
        ]);

        $task->save();

        // Create subtasks if provided
        if (isset($validatedData['subtasks'])) {
            foreach ($validatedData['subtasks'] as $subtaskData) {
                $subtask = new Subtask([
                    'title' => $subtaskData['title'],
                    'task_id' => $task->id,
                ]);
                $subtask->save();
            }
        }

        // Return response with created task
        $task->load('subtasks');  // Load subtasks relationship

        return response()->json($task, 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $task = Task::where('user_id', $user->id)->with('subtasks')->findOrFail($id);

        return response()->json($task);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'label' => 'nullable|string',
            'priority' => 'nullable|string',
            'due_date' => 'nullable|date',
            'reminder' => 'nullable|date',
            'subtasks' => 'nullable|array',  // Subtasks should be an array if provided
            'subtasks.*.title' => 'required|string|max:255',  // Validation for each subtask title
        ]);

        $user = Auth::user();
        $task = Task::where('user_id', $user->id)->findOrFail($id);

        // Update task
        $task->title = $validatedData['title'];
        $task->description = $validatedData['description'];
        $task->category = $validatedData['category'];
        $task->label = $validatedData['label'];
        $task->priority = $validatedData['priority'];
        $task->due_date = $validatedData['due_date'];
        $task->reminder = $validatedData['reminder'];
        $task->save();

        // Update or create subtasks if provided
        if (isset($validatedData['subtasks'])) {
            foreach ($validatedData['subtasks'] as $subtaskData) {
                if (isset($subtaskData['id'])) {
                    // Update existing subtask
                    $subtask = Subtask::where('task_id', $task->id)->findOrFail($subtaskData['id']);
                    $subtask->title = $subtaskData['title'];
                    $subtask->save();
                } else {
                    // Create new subtask
                    $subtask = new Subtask([
                        'title' => $subtaskData['title'],
                        'task_id' => $task->id,
                    ]);
                    $subtask->save();
                }
            }
        }

        // Return response with updated task
        $task->load('subtasks');  // Load subtasks relationship

        return response()->json($task);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $task = Task::where('user_id', $user->id)->findOrFail($id);

        // Delete subtasks first
        $task->subtasks()->delete();

        // Delete task
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
