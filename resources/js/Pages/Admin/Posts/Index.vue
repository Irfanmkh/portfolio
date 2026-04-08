<template>
	<AdminLayout>

		<Head title="Posts" />

		<SectionHeader section="Posts" title="Blog records" description="Manage Draft and published posts for the blog section.">
			<template #actions>
				<Link href="/admin/content" class="inline-flex items-center gap-2 rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/5">
					<IconArrowLeft class="h-4 w-4" />
					<span>Content hub</span>
				</Link>
				<Link href="/admin/posts/create" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
					<IconPlus class="h-4 w-4" />
					<span>New post</span>
				</Link>
			</template>

			<form class="mt-6 grid gap-4 xl:grid-cols-[2fr_1fr_1fr_auto]" @submit.prevent="applyFilters">
				<div>
					<label class="mb-2 block text-sm font-medium text-slate-300">Search</label>
					<input v-model="filters.search" type="search" placeholder="Title, slug, or content" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
				</div>

				<SelectField v-model="filters.status" label="Status">
					<option value="all">All statuses</option>
					<option v-for="status in filterOptions.statuses" :key="status" :value="status">{{ status }}</option>
				</SelectField>

				<SelectField v-model="filters.type" label="Type">
					<option value="all">All types</option>
					<option v-for="type in filterOptions.types" :key="type" :value="type">{{ type }}</option>
				</SelectField>

				<div class="flex items-end gap-2">
					<button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-amber-400 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
						<IconSearch class="h-4 w-4" />
						<span>Apply</span>
					</button>
					<button type="button" class="inline-flex items-center gap-2 rounded-2xl border border-white/10 px-4 py-3 text-sm text-slate-200 transition hover:bg-white/5" @click="resetFilters">
						<IconRefresh class="h-4 w-4" />
						<span>Reset</span>
					</button>
				</div>
			</form>

			<div class="mt-6 overflow-x-auto rounded-2xl border border-white/10">
				<table class="min-w-[760px] w-full text-left text-sm">
					<thead class="bg-slate-900/80 text-slate-400">
						<tr>
							<th class="px-4 py-3 font-medium">Title</th>
							<th class="px-4 py-3 font-medium">Status</th>
							<th class="px-4 py-3 font-medium">Published</th>
							<th class="px-4 py-3 font-medium">Slug</th>
							<th class="px-4 py-3 font-medium text-right">Actions</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-white/5 bg-slate-950/50">
						<tr v-for="post in posts" :key="post.id">
							<td class="px-4 py-3 text-white">{{ post.title }}</td>
							<td class="px-4 py-3 text-slate-300">{{ post.status }}</td>
							<td class="px-4 py-3 text-slate-300">{{ post.published_at || '—' }}</td>
							<td class="px-4 py-3 text-slate-500">
								<a :href="postSlugUrl(post)" target="_blank" rel="noopener noreferrer" class="inline-flex max-w-full items-center gap-2 rounded-lg border border-white/10 px-2 py-1 text-amber-300 transition hover:border-amber-400/30 hover:bg-amber-400/10 hover:text-amber-200">
									<span class="truncate">{{ post.slug }}</span>
								</a>
							</td>
							<td class="px-4 py-3">
								<div class="flex justify-end gap-2">
									<Link :href="`/admin/posts/${post.id}/edit`" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-3 py-2 text-xs text-slate-200 transition hover:bg-white/5">
										<IconEdit class="h-3.5 w-3.5" />
										<span>Edit</span>
									</Link>
									<button type="button" class="inline-flex items-center gap-2 rounded-lg border border-rose-400/20 px-3 py-2 text-xs text-rose-200 transition hover:bg-rose-400/10" @click="destroyPost(post.id)">
										<IconTrash class="h-3.5 w-3.5" />
										<span>Delete</span>
									</button>
								</div>
							</td>
						</tr>
						<tr v-if="!posts.length">
							<td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">No posts found.</td>
						</tr>
					</tbody>
				</table>
			</div>

			<Pagination :links="postsMeta.links ?? []" />
		</SectionHeader>
	</AdminLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SectionHeader from '@/Components/Admin/SectionHeader.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { IconArrowLeft, IconEdit, IconPlus, IconRefresh, IconSearch, IconTrash } from '@tabler/icons-vue';

const page = usePage();
const postsMeta = computed(() => page.props.posts ?? { data: [], links: [] });
const posts = computed(() => postsMeta.value.data ?? []);
const filterOptions = computed(() => page.props.filterOptions ?? { statuses: ['all'], types: [] });
const initialFilters = page.props.filters ?? { search: '', status: 'all', type: 'all' };

const filters = reactive({
	search: initialFilters.search ?? '',
	status: initialFilters.status ?? 'all',
	type: initialFilters.type ?? 'all',
});

function applyFilters() {
	router.get('/admin/posts', { ...filters }, {
		preserveScroll: true,
		preserveState: true,
		replace: true,
	});
}

function resetFilters() {
	filters.search = '';
	filters.status = 'all';
	filters.type = 'all';
	applyFilters();
}

function postSlugUrl(post) {
	if (post?.status === 'published' && post?.slug) {
		return `/blogs/${post.slug}`;
	}

	return `/admin/posts/${post.id}/edit`;
}

function destroyPost(id) {
	if (!window.confirm('Delete this post?')) {
		return;
	}

	router.delete(`/admin/posts/${id}`, {
		preserveScroll: true,
	});
}
</script>