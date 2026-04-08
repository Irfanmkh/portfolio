<?php

namespace App\Support;

use App\Models\Experience;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class PortfolioData
{
	public static function storageUrl(mixed $path): ?string
	{
		if (is_array($path) || is_object($path)) {
			$normalizedPath = self::normalizeStoredPath($path);

			return $normalizedPath ? self::storageUrl($normalizedPath) : null;
		}

		if (! $path) {
			return null;
		}

		if (preg_match('/^https?:\/\//i', $path)) {
			return $path;
		}

		if (str_starts_with($path, '/storage/')) {
			return url($path);
		}

		return url('/storage/' . ltrim($path, '/'));
	}

	public static function truncateWords(?string $text, int $limit = 20): string
	{
		if (! $text) {
			return '';
		}

		$words = preg_split('/\s+/', trim($text)) ?: [];

		if (count($words) <= $limit) {
			return implode(' ', $words);
		}

		return implode(' ', array_slice($words, 0, $limit)) . '...';
	}

	public static function formatDateRange(mixed $start, mixed $end): string
	{
		if (! $start && ! $end) {
			return '';
		}

		$startYear = self::dateYear($start);
		$endYear = self::dateYear($end);

		if ($startYear && $endYear) {
			return $startYear . ' - ' . $endYear;
		}

		if ($startYear && ! $endYear) {
			return $startYear . ' - Present';
		}

		return $endYear ? $endYear : '';
	}

	public static function normalizeTextList(array $values): array
	{
		$normalized = [];

		foreach ($values as $value) {
			$item = self::normalizeTextValue($value);

			if ($item !== null) {
				$normalized[] = $item;
			}
		}

		return array_values($normalized);
	}

	public static function normalizeTextValue(mixed $value): ?string
	{
		if (is_string($value) || is_numeric($value)) {
			$text = trim((string) $value);

			return $text !== '' ? $text : null;
		}

		if (is_array($value)) {
			foreach (['name', 'title', 'label', 'text', 'value', 'caption'] as $key) {
				if (array_key_exists($key, $value)) {
					$text = self::normalizeTextValue($value[$key]);

					if ($text !== null) {
						return $text;
					}
				}
			}

			foreach ($value as $item) {
				$text = self::normalizeTextValue($item);

				if ($text !== null) {
					return $text;
				}
			}
		}

		if (is_object($value)) {
			foreach (['name', 'title', 'label', 'text', 'value', 'caption'] as $key) {
				if (isset($value->{$key})) {
					$text = self::normalizeTextValue($value->{$key});

					if ($text !== null) {
						return $text;
					}
				}
			}

			foreach (get_object_vars($value) as $item) {
				$text = self::normalizeTextValue($item);

				if ($text !== null) {
					return $text;
				}
			}
		}

		return null;
	}

	public static function normalizeStoredPath(mixed $value): ?string
	{
		if (is_string($value) || is_numeric($value)) {
			$path = trim((string) $value);

			return $path !== '' ? $path : null;
		}

		if (is_array($value)) {
			foreach (['image', 'path', 'url', 'src', 'file', 'value'] as $key) {
				if (array_key_exists($key, $value)) {
					$path = self::normalizeStoredPath($value[$key]);

					if ($path) {
						return $path;
					}
				}
			}

			foreach ($value as $item) {
				$path = self::normalizeStoredPath($item);

				if ($path) {
					return $path;
				}
			}
		}

		return null;
	}

	public static function normalizeStoredPaths(array $values): array
	{
		$normalized = [];

		foreach ($values as $value) {
			$path = self::normalizeStoredPath($value);

			if ($path !== null) {
				$normalized[] = $path;
			}
		}

		return array_values($normalized);
	}

	public static function seo(array $overrides = []): array
	{
		$defaults = [
			'site_name' => setting('owner_name', config('app.name', 'Portfolio')),
			'title' => setting('seo_title', setting('owner_name', config('app.name', 'Portfolio'))),
			'description' => setting('seo_description', setting('description', '')),
			'keywords' => setting('seo_keywords', ''),
			'author' => setting('seo_author', setting('owner_name', config('app.name', 'Portfolio'))),
			'image' => setting('seo_image', null, true) ?: setting('profile_picture', null, true),
			'canonical' => rtrim((string) config('app.url', url('/')), '/'),
			'url' => rtrim((string) config('app.url', url('/')), '/'),
			'robots' => 'index,follow',
			'type' => 'website',
			'locale' => app()->getLocale(),
			'theme_color' => '#071223',
			'twitter_site' => setting('seo_twitter_site', ''),
			'twitter_creator' => setting('seo_twitter_creator', ''),
			'custom_meta' => self::seoMetaFromJson(setting('seo_custom_meta_json', '[]')),
		];

		$seo = array_merge($defaults, $overrides);

		$seo['site_name'] = self::normalizeSeoText($seo['site_name']) ?: 'Portfolio';
		$seo['title'] = self::seoTitle($seo['title'] ?? '', $seo['site_name']);
		$seo['description'] = self::normalizeSeoText($seo['description'] ?? '');
		$seo['keywords'] = self::normalizeSeoKeywords($seo['keywords'] ?? '');
		$seo['author'] = self::normalizeSeoText($seo['author'] ?? $seo['site_name']);
		$seo['image'] = self::normalizeSeoUrl($seo['image'] ?? null);
		$seo['canonical'] = self::normalizeSeoUrl($seo['canonical'] ?? null) ?: rtrim((string) config('app.url', url('/')), '/');
		$seo['url'] = self::normalizeSeoUrl($seo['url'] ?? null) ?: $seo['canonical'];
		$seo['robots'] = self::normalizeSeoText($seo['robots'] ?? '') ?: 'index,follow';
		$seo['type'] = self::normalizeSeoText($seo['type'] ?? '') ?: 'website';
		$seo['locale'] = app()->getLocale();
		$seo['theme_color'] = '#071223';
		$seo['twitter_site'] = self::normalizeSeoText($seo['twitter_site'] ?? '');
		$seo['twitter_creator'] = self::normalizeSeoText($seo['twitter_creator'] ?? '');
		$seo['custom_meta'] = self::normalizeSeoMeta(array_merge(
			self::seoMetaFromJson(setting('seo_custom_meta_json', '[]')),
			self::seoMetaFromJson($seo['custom_meta'] ?? [])
		));

		return $seo;
	}

	public static function seoTitle(?string $title, ?string $siteName = null): string
	{
		$normalizedSiteName = self::normalizeSeoText($siteName ?? setting('owner_name', config('app.name', 'Portfolio')));
		$normalizedSiteName = $normalizedSiteName ?: 'Portfolio';
		$normalizedTitle = self::normalizeSeoText($title);

		if (! $normalizedTitle) {
			return $normalizedSiteName;
		}

		if (str_contains(mb_strtolower($normalizedTitle), mb_strtolower($normalizedSiteName))) {
			return $normalizedTitle;
		}

		return $normalizedTitle . ' | ' . $normalizedSiteName;
	}

	public static function seoMetaFromJson(mixed $value): array
	{
		if (is_array($value)) {
			return $value;
		}

		if (! is_string($value)) {
			return [];
		}

		$decoded = json_decode(trim($value), true);

		return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
	}

	public static function normalizeSeoMeta(array $meta): array
	{
		$normalized = [];

		foreach ($meta as $item) {
			if (! is_array($item) && ! is_object($item)) {
				continue;
			}

			$source = is_object($item) ? get_object_vars($item) : $item;
			$entry = [];

			foreach (['name', 'property', 'content', 'http_equiv', 'charset', 'media', 'key'] as $field) {
				if (! array_key_exists($field, $source)) {
					continue;
				}

				$value = self::normalizeSeoText($source[$field]);

				if ($value !== null) {
					$entry[$field] = $value;
				}
			}

			if (! isset($entry['content']) && ! isset($entry['charset'])) {
				continue;
			}

			$normalized[] = $entry;
		}

		return $normalized;
	}

	public static function normalizeSeoKeywords(mixed $keywords): string
	{
		if (is_array($keywords)) {
			$keywords = implode(', ', array_filter(array_map(fn ($value) => self::normalizeSeoText($value), $keywords)));
		}

		$normalized = self::normalizeSeoText($keywords);

		if (! $normalized) {
			return '';
		}

		$pieces = array_values(array_filter(array_map(
			fn ($value) => self::normalizeSeoText($value),
			explode(',', $normalized)
		)));

		$pieces = array_values(array_unique($pieces));

		return implode(', ', $pieces);
	}

	public static function normalizeSeoText(mixed $value): ?string
	{
		if (is_string($value) || is_numeric($value)) {
			$text = trim((string) $value);

			return $text !== '' ? $text : null;
		}

		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}

		return null;
	}

	public static function normalizeSeoUrl(mixed $value): ?string
	{
		$text = self::normalizeSeoText($value);

		if (! $text) {
			return null;
		}

		return rtrim($text, '/');
	}

	public static function projectSummary(Project $project): array
	{
		return [
			'id' => $project->id,
			'title' => $project->title,
			'slug' => $project->slug,
			'year' => $project->year,
			'made_at' => $project->made_at,
			'description' => $project->description,
			'short_description' => self::truncateWords($project->description, 20),
			'link' => $project->link,
			'thumbnail_url' => self::storageUrl($project->thumbnail),
			'status' => $project->status,
		];
	}

	public static function projectFormPayload(Project $project): array
	{
		return [
			'id' => $project->id,
			'title' => $project->title,
			'slug' => $project->slug,
			'year' => $project->year,
			'made_at' => $project->made_at,
			'description' => $project->description,
			'link' => $project->link,
			'status' => $project->status,
			'thumbnail' => $project->thumbnail,
			'thumbnail_url' => self::storageUrl($project->thumbnail),
			'gallery' => $project->gallery ?? [],
			'gallery_urls' => collect($project->gallery ?? [])
				->map(fn ($path) => self::storageUrl(self::normalizeStoredPath($path)))
				->filter()
				->values()
				->all(),
			'technologies' => self::normalizeTextList($project->technologies ?? []),
			'features' => self::normalizeTextList($project->features ?? []),
			'results' => self::normalizeTextList($project->results ?? []),
		];
	}

	public static function emptyProjectPayload(): array
	{
		return [
			'id' => null,
			'title' => '',
			'slug' => '',
			'year' => null,
			'made_at' => '',
			'description' => '',
			'link' => '',
			'status' => 'Completed',
			'thumbnail' => null,
			'thumbnail_url' => null,
			'gallery' => [],
			'gallery_urls' => [],
			'technologies' => [],
			'features' => [],
			'results' => [],
		];
	}

	public static function postSummary(Post $post): array
	{
		return [
			'id' => $post->id,
			'title' => $post->title,
			'short_title' => self::truncateWords($post->title, 12),
			'slug' => $post->slug,
			'cover_image_url' => self::storageUrl($post->cover_image),
			'published_at' => $post->published_at ? $post->published_at->toDateString() : null,
			'status' => $post->status,
			'type' => $post->type,
		];
	}

	public static function postFormPayload(Post $post): array
	{
		return [
			'id' => $post->id,
			'title' => $post->title,
			'slug' => $post->slug,
			'content' => $post->content,
			'type' => $post->type,
			'status' => $post->status,
			'cover_image_url' => self::storageUrl($post->cover_image),
			'published_at' => $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '',
		];
	}

	public static function emptyPostPayload(): array
	{
		return [
			'id' => null,
			'title' => '',
			'slug' => '',
			'content' => '',
			'type' => 'post',
			'status' => 'Draft',
			'cover_image_url' => null,
			'published_at' => '',
		];
	}

	public static function experienceSummary(Experience $experience): array
	{
		return [
			'id' => $experience->id,
			'position' => $experience->position,
			'company' => $experience->company,
			'company_link' => $experience->company_link,
			'location' => $experience->location,
			'start_date' => $experience->start_date?->format('Y-m-d') ?? '',
			'end_date' => $experience->end_date?->format('Y-m-d') ?? '',
			'date_range' => self::formatDateRange($experience->start_date, $experience->end_date),
			'employment_type' => $experience->employment_type,
			'description' => $experience->description,
			'highlights' => array_values($experience->highlights ?? []),
			'tech_stack' => array_values($experience->tech_stack ?? []),
		];
	}

	public static function experienceFormPayload(Experience $experience): array
	{
		return self::experienceSummary($experience);
	}

	public static function emptyExperiencePayload(): array
	{
		return [
			'id' => null,
			'position' => '',
			'company' => '',
			'company_link' => '',
			'location' => '',
			'start_date' => '',
			'end_date' => '',
			'employment_type' => 'Full-time',
			'description' => '',
			'highlights' => [],
			'tech_stack' => [],
		];
	}

	public static function storeFile(?UploadedFile $file, string $folder): ?string
	{
		return $file ? $file->store($folder, 'public') : null;
	}

	public static function deleteStoredFile(?string $path): void
	{
		if (! $path || preg_match('/^https?:\/\//i', $path)) {
			return;
		}

		Storage::disk('public')->delete($path);
	}

	private static function dateYear(mixed $value): ?string
	{
		if ($value instanceof \DateTimeInterface) {
			return $value->format('Y');
		}

		return null;
	}
}