<template>
	<form class="space-y-8" @submit.prevent="submit">
		<section class="grid gap-6 xl:grid-cols-2">
			<div class="rounded-[2rem] border border-white/10 bg-white/5 p-6">
				<div class="text-xs uppercase tracking-[0.3em] text-slate-500">Post</div>
				<h2 class="mt-2 text-xl font-semibold text-white">Core details</h2>

				<div class="mt-6 space-y-5">
					<div>
						<label class="mb-2 block text-sm font-medium text-slate-300">Title</label>
						<input v-model="form.title" type="text" placeholder="Post title" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
						<p v-if="form.errors.title" class="mt-2 text-sm text-rose-300">{{ form.errors.title }}</p>
					</div>

					<div>
						<label class="mb-2 block text-sm font-medium text-slate-300">Slug</label>
						<input v-model="form.slug" type="text" placeholder="post-title-slug" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
					</div>

					<div class="grid gap-4 sm:grid-cols-2">
						<div>
							<label class="mb-2 block text-sm font-medium text-slate-300">Type</label>
							<input v-model="form.type" type="text" placeholder="tutorial, news, or opinion" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
						</div>

						<SelectField v-model="form.status" label="Status" :error="form.errors.status">
							<option value="Draft">Draft</option>
							<option value="Published">Published</option>
						</SelectField>
					</div>

					<div>
						<label class="mb-2 block text-sm font-medium text-slate-300">Published at</label>
						<input v-model="form.published_at" type="datetime-local" placeholder="2026-03-22 14:30" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
					</div>
				</div>
			</div>

			<div class="rounded-[2rem] border border-white/10 bg-white/5 p-6">
				<div class="text-xs uppercase tracking-[0.3em] text-slate-500">Content</div>
				<h2 class="mt-2 text-xl font-semibold text-white">Body and cover image</h2>

				<div class="mt-6 space-y-5">
					<div>
						<FileUploadField label="Cover image" accept="image/*" :existing-url="post.cover_image_url" button-label="Upload cover image" helper-text="Use a clear banner image for the post header." :error="form.errors.cover_image" @change="file => handleFile(file, 'cover_image')" />
					</div>

						<RichTextEditor v-model="form.content" label="Content" hint="Compose the article with headings, lists, links, and rich formatting." placeholder="Write the full article content here." :error="form.errors.content" />
				</div>
			</div>
		</section>

		<div class="flex flex-wrap items-center justify-between gap-4 rounded-[2rem] border border-white/10 bg-white/5 p-5">
			<Link :href="backUrl" class="inline-flex items-center gap-2 rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/5">
				<IconArrowLeft class="h-4 w-4" />
				<span>Back</span>
			</Link>
			<button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
				<IconDeviceFloppy class="h-4 w-4" />
				{{ form.processing ? 'Saving...' : submitLabel }}
			</button>
		</div>
	</form>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { IconArrowLeft, IconDeviceFloppy } from '@tabler/icons-vue';
import FileUploadField from '@/Components/Form/FileUploadField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';

const props = defineProps({
	post: { type: Object, required: true },
	submitUrl: { type: String, required: true },
	method: { type: String, default: 'post' },
	submitLabel: { type: String, default: 'Save post' },
	backUrl: { type: String, default: '/admin/posts' },
});

const form = useForm({
	_method: props.method === 'put' ? 'put' : '',
	title: props.post.title ?? '',
	slug: props.post.slug ?? '',
	content: props.post.content ?? '',
	type: props.post.type ?? 'post',
	status: props.post.status ?? 'Draft',
	cover_image: null,
	published_at: props.post.published_at ?? '',
});

function handleFile(file, field) {
	form[field] = file ?? null;
}

function submit() {
	const options = {
		preserveScroll: true,
		forceFormData: true,
	};

	if (props.method === 'put') {
		form._method = 'put';
		form.post(props.submitUrl, options);
		return;
	}

	form._method = '';
	form.post(props.submitUrl, options);
}
</script>