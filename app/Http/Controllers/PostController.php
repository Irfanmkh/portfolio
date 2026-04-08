<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Support\PortfolioData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
	public function index(Request $request): Response
	{
		$search = trim((string) $request->query('search', ''));
		$status = trim((string) $request->query('status', 'all'));
		$type = trim((string) $request->query('type', 'all'));

		$postsQuery = Post::query()
			->when($search !== '', function ($query) use ($search) {
				$query->where(function ($nestedQuery) use ($search) {
					$nestedQuery->where('title', 'like', '%' . $search . '%')
						->orWhere('slug', 'like', '%' . $search . '%')
						->orWhere('content', 'like', '%' . $search . '%');
				});
			})
			->when(in_array($status, ['Draft', 'Published'], true), fn ($query) => $query->where('status', $status))
			->when($type !== 'all' && $type !== '', fn ($query) => $query->where('type', $type));

		$posts = $postsQuery
			->orderByDesc('published_at')
			->orderByDesc('created_at')
			->paginate(10)
			->withQueryString();

		$posts->getCollection()->transform(fn (Post $post) => PortfolioData::postSummary($post));

		return Inertia::render('Admin/Posts/Index', [
			'posts' => $posts,
			'filters' => [
				'search' => $search,
				'status' => $status,
				'type' => $type,
			],
			'filterOptions' => [
				'statuses' => ['all', 'Draft', 'published'],
				'types' => Post::query()
					->select('type')
					->distinct()
					->orderBy('type')
					->pluck('type')
					->filter()
					->values(),
			],
		]);
	}

	public function create(): Response
	{
		return Inertia::render('Admin/Posts/Create', [
			'post' => PortfolioData::emptyPostPayload(),
		]);
	}

	public function store(PostRequest $request): RedirectResponse
	{
		$data = $request->validated();

		$post = new Post();
		$this->fillPost($post, $request, $data);
		$post->save();

		return redirect()->route('admin.posts.index')->with('success', 'Post created successfully.');
	}

	public function edit(Post $post): Response
	{
		return Inertia::render('Admin/Posts/Edit', [
			'post' => PortfolioData::postFormPayload($post),
		]);
	}

	public function update(PostRequest $request, Post $post): RedirectResponse
	{
		$data = $request->validated();

		$this->fillPost($post, $request, $data);
		$post->save();

		return redirect()->route('admin.posts.edit', $post)->with('success', 'Post updated successfully.');
	}

	public function destroy(Post $post): RedirectResponse
	{
		PortfolioData::deleteStoredFile($post->cover_image);
		$post->delete();

		return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully.');
	}

	private function fillPost(Post $post, Request $request, array $data): void
	{
		$post->title = $data['title'];
		$post->slug = $this->resolveSlug($data['title'], $data['slug'] ?? null, $post->id);
		$post->content = $data['content'] ?? null;
		$post->type = $data['type'] ?: 'post';
		$post->status = $data['status'];
		$post->published_at = $data['published_at'] ?: null;

		if ($request->hasFile('cover_image')) {
			PortfolioData::deleteStoredFile($post->cover_image);
			$post->cover_image = PortfolioData::storeFile($request->file('cover_image'), 'posts');
		}
	}

	private function resolveSlug(string $title, ?string $slug, ?int $ignoreId = null): string
	{
		$baseSlug = Str::slug($slug ?: $title) ?: 'post';
		$candidate = $baseSlug;
		$index = 2;

		while (Post::query()
			->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
			->where('slug', $candidate)
			->exists()) {
			$candidate = $baseSlug . '-' . $index;
			$index++;
		}

		return $candidate;
	}
}