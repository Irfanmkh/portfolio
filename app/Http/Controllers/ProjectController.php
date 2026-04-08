<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Support\PortfolioData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
	public function index(Request $request): Response
	{
		$search = trim((string) $request->query('search', ''));
		$status = trim((string) $request->query('status', 'all'));
		$year = trim((string) $request->query('year', ''));

		$projectsQuery = Project::query()
			->when($search !== '', function ($query) use ($search) {
				$query->where(function ($nestedQuery) use ($search) {
					$nestedQuery->where('title', 'like', '%' . $search . '%')
						->orWhere('slug', 'like', '%' . $search . '%')
						->orWhere('made_at', 'like', '%' . $search . '%')
						->orWhere('description', 'like', '%' . $search . '%')
						->orWhere('link', 'like', '%' . $search . '%');
				});
			})
			->when(in_array($status, ['Ongoing', 'Completed'], true), fn ($query) => $query->where('status', $status))
			->when($year !== '', fn ($query) => $query->where('year', (int) $year));

		$projects = $projectsQuery
			->orderByDesc('created_at')
			->paginate(10)
			->withQueryString();

		$projects->getCollection()->transform(fn (Project $project) => PortfolioData::projectSummary($project));

		return Inertia::render('Admin/Projects/Index', [
			'projects' => $projects,
			'filters' => [
				'search' => $search,
				'status' => $status,
				'year' => $year,
			],
			'filterOptions' => [
				'statuses' => ['all', 'Ongoing', 'Completed'],
				'years' => Project::query()
					->whereNotNull('year')
					->select('year')
					->distinct()
					->orderByDesc('year')
					->pluck('year')
					->map(fn ($value) => (string) $value)
					->values(),
			],
		]);
	}

	public function create(): Response
	{
		return Inertia::render('Admin/Projects/Create', [
			'project' => PortfolioData::emptyProjectPayload(),
		]);
	}

	public function store(ProjectRequest $request): RedirectResponse
	{
		$data = $request->validated();

		$project = new Project();
		$this->fillProject($project, $request, $data);
		$project->save();

		return redirect()->route('admin.projects.index')->with('success', 'Project created successfully.');
	}

	public function edit(Project $project): Response
	{
		return Inertia::render('Admin/Projects/Edit', [
			'project' => PortfolioData::projectFormPayload($project),
		]);
	}

	public function update(ProjectRequest $request, Project $project): RedirectResponse
	{
		$data = $request->validated();

		$this->fillProject($project, $request, $data);
		$project->save();

		return redirect()->route('admin.projects.edit', $project)->with('success', 'Project updated successfully.');
	}

	public function destroy(Project $project): RedirectResponse
	{
		PortfolioData::deleteStoredFile($project->thumbnail);

		foreach ($project->gallery ?? [] as $path) {
			PortfolioData::deleteStoredFile(PortfolioData::normalizeStoredPath($path));
		}

		$project->delete();

		return redirect()->route('admin.projects.index')->with('success', 'Project deleted successfully.');
	}

	private function fillProject(Project $project, Request $request, array $data): void
	{
		$project->title = $data['title'];
		$project->slug = $this->resolveSlug($data['title'], $data['slug'] ?? null, $project->id);
		$project->year = $data['year'] ?? null;
		$project->made_at = $data['made_at'] ?? null;
		$project->description = $data['description'] ?? null;
		$project->link = $data['link'] ?? null;
		$project->status = $data['status'];
		$project->technologies = PortfolioData::normalizeTextList($data['technologies'] ?? []);
		$project->features = PortfolioData::normalizeTextList($data['features'] ?? []);
		$project->results = PortfolioData::normalizeTextList($data['results'] ?? []);

		$gallery = PortfolioData::normalizeStoredPaths($project->exists ? ($project->gallery ?? []) : []);
		$galleryRemove = PortfolioData::normalizeStoredPaths($request->input('gallery_remove', []));

		if ($galleryRemove) {
			foreach ($galleryRemove as $path) {
				PortfolioData::deleteStoredFile($path);
			}

			$gallery = array_values(array_diff($gallery, $galleryRemove));
		}

		foreach ($request->file('gallery_files', []) as $file) {
			$storedPath = PortfolioData::storeFile($file, 'projects');

			if ($storedPath) {
				$gallery[] = $storedPath;
			}
		}

		$project->gallery = $gallery;

		if ($request->hasFile('thumbnail')) {
			PortfolioData::deleteStoredFile($project->thumbnail);
			$project->thumbnail = PortfolioData::storeFile($request->file('thumbnail'), 'projects');
		} elseif ($request->boolean('thumbnail_remove')) {
			PortfolioData::deleteStoredFile($project->thumbnail);
			$project->thumbnail = null;
		}
	}

	private function resolveSlug(string $title, ?string $slug, ?int $ignoreId = null): string
	{
		$baseSlug = Str::slug($slug ?: $title) ?: 'project';
		$candidate = $baseSlug;
		$index = 2;

		while (Project::query()
			->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
			->where('slug', $candidate)
			->exists()) {
			$candidate = $baseSlug . '-' . $index;
			$index++;
		}

		return $candidate;
	}
}