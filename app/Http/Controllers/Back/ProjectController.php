<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::latest()->paginate(15);
        return view('back.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('back.projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:projects',
            'description' => 'nullable|string',
            'repository_url' => 'nullable|url',
            'status' => 'required|in:active,archived,pending',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        Project::create($validated);

        return redirect()->route('back.projects.index')
            ->with('success', 'پروژه با موفقیت ایجاد شد.');
    }

    public function show(Project $project)
    {
        $updates = $project->updates()->latest()->paginate(10);
        return view('back.projects.show', compact('project', 'updates'));
    }

    public function edit(Project $project)
    {
        return view('back.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:projects,slug,' . $project->id,
            'description' => 'nullable|string',
            'repository_url' => 'nullable|url',
            'status' => 'required|in:active,archived,pending',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $project->update($validated);

        return redirect()->route('back.projects.index')
            ->with('success', 'پروژه با موفقیت بروزرسانی شد.');
    }

    public function destroy(Project $project)
    {
        if ($project->updates()->count() > 0) {
            return back()->with('error', 'امکان حذف پروژه‌ای که دارای آپدیت است وجود ندارد.');
        }

        $project->delete();

        return redirect()->route('back.projects.index')
            ->with('success', 'پروژه با موفقیت حذف شد.');
    }
}
