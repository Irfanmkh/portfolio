<template>
    <form class="space-y-8" @submit.prevent="submit">
        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6">
                <div class="text-xs uppercase tracking-[0.3em] text-slate-500">Project</div>
                <h2 class="mt-2 text-xl font-semibold text-white">Core details</h2>

                <div class="mt-6 space-y-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Title</label>
                        <input v-model="form.title" type="text" placeholder="Project title" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
                        <p v-if="form.errors.title" class="mt-2 text-sm text-rose-300">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Slug</label>
                        <input v-model="form.slug" type="text" placeholder="project-title-slug" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
                        <p v-if="form.errors.slug" class="mt-2 text-sm text-rose-300">{{ form.errors.slug }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-300">Year</label>
                            <input v-model="form.year" type="number" min="1900" max="2100" placeholder="2026" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-300">Made at</label>
                            <input v-model="form.made_at" type="text" placeholder="Company, agency, or client" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Link</label>
                        <input v-model="form.link" type="url" placeholder="https://example.com" class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20" />
                        <p v-if="form.errors.link" class="mt-2 text-sm text-rose-300">{{ form.errors.link }}</p>
                    </div>

                    <SelectField v-model="form.status" label="Status" :error="form.errors.status">
                        <option value="Completed">Completed</option>
                        <option value="Ongoing">Ongoing</option>
                    </SelectField>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6">
                <div class="text-xs uppercase tracking-[0.3em] text-slate-500">Assets</div>
                <h2 class="mt-2 text-xl font-semibold text-white">Images and rich data</h2>

                <div class="mt-6 space-y-5">
                    <div>
                        <FileUploadField
                            label="Thumbnail"
                            accept="image/*"
                            :existing-url="project.thumbnail_url"
                            :existing-name="project.thumbnail_name || ''"
                            :allow-existing-removal="!!project.thumbnail_url"
                            button-label="Upload thumbnail"
                            helper-text="Recommended size is 16:9 for the best preview on cards and detail pages."
                            :error="form.errors.thumbnail"
                            @change="file => handleFile(file, 'thumbnail')"
                            @existing-remove="removeThumbnail"
                        />
                    </div>

                    <div>
                        <FileUploadField
                            label="Gallery images"
                            accept="image/*"
                            multiple
                            :existing-items="existingGalleryItems"
                            :allow-existing-removal="true"
                            button-label="Upload gallery images"
                            helper-text="Upload multiple images at once. New uploads will be added to the current gallery."
                            :error="form.errors.gallery_files || form.errors['gallery_files.0']"
                            @change="files => handleGalleryChange(files)"
                            @existing-remove="removeGalleryItem"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Description</label>
                        <textarea v-model="form.description" rows="6" placeholder="Describe the project, goals, scope, and outcome." class="w-full rounded-2xl border border-white/10 bg-slate-950/80 px-4 py-3 text-white outline-none focus:border-amber-400/50 focus:ring-2 focus:ring-amber-400/20"></textarea>
                    </div>

                    <MultiTextListInput v-model="form.technologies" label="Technologies" :error="form.errors.technologies" />

                    <MultiTextListInput v-model="form.features" label="Features" :error="form.errors.features" />

                    <MultiTextListInput v-model="form.results" label="Results" :error="form.errors.results" />
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
import MultiTextListInput from '@/Components/Admin/MultiTextListInput.vue';
import FileUploadField from '@/Components/Form/FileUploadField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { IconArrowLeft, IconDeviceFloppy } from '@tabler/icons-vue';

const props = defineProps({
    project: { type: Object, required: true },
    submitUrl: { type: String, required: true },
    method: { type: String, default: 'post' },
    submitLabel: { type: String, default: 'Save project' },
    backUrl: { type: String, default: '/admin/projects' },
});

const form = useForm({
    _method: props.method === 'put' ? 'put' : '',
    title: props.project.title ?? '',
    slug: props.project.slug ?? '',
    year: props.project.year ?? '',
    made_at: props.project.made_at ?? '',
    description: props.project.description ?? '',
    link: props.project.link ?? '',
    status: props.project.status ?? 'Completed',
    thumbnail: null,
    thumbnail_remove: false,
    gallery_files: [],
    gallery_remove: [],
    technologies: normalizeList(props.project.technologies ?? []),
    features: normalizeList(props.project.features ?? []),
    results: normalizeList(props.project.results ?? []),
});

const existingGalleryItems = (props.project.gallery ?? []).map((item, index) => {
    const path = resolveStoredPath(item);
    const previewUrl = props.project.gallery_urls?.[index] ?? '';
    const name = resolveDisplayName(item, index, path);

    return {
        id: path || `gallery-${index}`,
        path,
        name,
        previewUrl,
        mimeType: inferMimeTypeFromPath(path || name),
        removable: true,
    };
});

function handleFile(file, field) {
    form[field] = file ?? null;

    if (field === 'thumbnail' && file) {
        form.thumbnail_remove = false;
    }
}

function handleGalleryChange(files) {
    form.gallery_files = Array.isArray(files) ? files : [];
}

function removeThumbnail() {
    form.thumbnail = null;
    form.thumbnail_remove = true;
}

function removeGalleryItem(item) {
    const path = item?.path || item?.id;

    if (!path) {
        return;
    }

    form.gallery_remove = Array.from(new Set([...form.gallery_remove, path]));
}

function inferNameFromPath(path, index) {
    const filename = String(path ?? '')
        .split('?')[0]
        .split('/')
        .filter(Boolean)
        .pop();

    if (filename) {
        return filename;
    }

    return `image-${index + 1}`;
}

function resolveDisplayName(item, index, path) {
    if (typeof item === 'string' || typeof item === 'number') {
        const text = String(item).trim();

        return text !== '' ? inferNameFromPath(text, index) : `image-${index + 1}`;
    }

    if (item && typeof item === 'object') {
        for (const key of ['name', 'title', 'label', 'text', 'value', 'caption']) {
            if (Object.prototype.hasOwnProperty.call(item, key)) {
                const text = normalizeTextValue(item[key]);

                if (text) {
                    return text;
                }
            }
        }
    }

    return inferNameFromPath(path, index);
}

function resolveStoredPath(item) {
    if (typeof item === 'string' || typeof item === 'number') {
        const text = String(item).trim();

        return text !== '' ? text : '';
    }

    if (!item || typeof item !== 'object') {
        return '';
    }

    for (const key of ['path', 'url', 'src', 'file', 'image', 'value']) {
        if (Object.prototype.hasOwnProperty.call(item, key)) {
            const text = normalizeTextValue(item[key]);

            if (text) {
                return text;
            }
        }
    }

    return '';
}

function normalizeTextValue(value) {
    if (typeof value === 'string' || typeof value === 'number') {
        const text = String(value).trim();

        return text !== '' ? text : null;
    }

    if (Array.isArray(value)) {
        for (const item of value) {
            const text = normalizeTextValue(item);

            if (text) {
                return text;
            }
        }
    }

    if (value && typeof value === 'object') {
        for (const key of ['name', 'title', 'label', 'text', 'value', 'caption', 'path', 'url', 'src', 'file', 'image']) {
            if (Object.prototype.hasOwnProperty.call(value, key)) {
                const text = normalizeTextValue(value[key]);

                if (text) {
                    return text;
                }
            }
        }

        for (const item of Object.values(value)) {
            const text = normalizeTextValue(item);

            if (text) {
                return text;
            }
        }
    }

    return null;
}

function inferMimeTypeFromPath(path) {
    return /\.(png|jpe?g|gif|webp|bmp|svg|avif)$/i.test(String(path)) ? 'image/*' : '';
}

function normalizeList(values) {
    const items = Array.isArray(values) ? values : [];
    const normalized = items
        .map((value) => normalizeListItem(value))
        .filter((value) => value !== null && value !== '');

    return normalized.length ? normalized : [''];
}

function normalizeListItem(value) {
    if (typeof value === 'string' || typeof value === 'number') {
        const text = String(value).trim();

        return text !== '' ? text : null;
    }

    if (Array.isArray(value)) {
        for (const item of value) {
            const text = normalizeListItem(item);

            if (text) {
                return text;
            }
        }
    }

    if (value && typeof value === 'object') {
        for (const key of ['name', 'title', 'label', 'text', 'value']) {
            if (Object.prototype.hasOwnProperty.call(value, key)) {
                const text = normalizeListItem(value[key]);

                if (text) {
                    return text;
                }
            }
        }

        for (const item of Object.values(value)) {
            const text = normalizeListItem(item);

            if (text) {
                return text;
            }
        }
    }

    return null;
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