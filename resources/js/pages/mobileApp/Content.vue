<template>
    <div>
        <div v-if="notice" class="ma-error ma-success" role="status">{{ notice }}</div>
        <div class="ma-panel">
            <div class="ma-panel-head">
                <div><h2>Fleet & Content</h2><p class="ma-muted">Upload photos and choose where they appear in the mobile app.</p></div>
                <button v-if="options.permissions.content_update" class="btn btn-success" @click="edit()"><i class="fas fa-plus mr-1"></i>Add image</button>
            </div>
            <MobileFilters id="ma-content" v-model="filters" :options="options" search="Image title" :statuses="['active','inactive']" :busy="loading" @apply="load(1,true)" />
            <div v-if="error" class="ma-error m-3" role="alert">{{ error }} <button class="btn btn-outline-danger btn-sm ml-2" @click="load()">Retry</button></div>
            <div class="ma-table-wrap"><table class="ma-table">
                <thead><tr><th>Image</th><th>Title</th><th>App placement</th><th>Category / Class</th><th>Order</th><th>Visibility</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    <tr v-if="loading"><td colspan="8" class="ma-empty" role="status">Loading app content…</td></tr>
                    <template v-else>
                        <tr v-for="item in items" :key="item.id">
                            <td><img :src="item.image_url" :alt="item.title" class="ma-content-thumbnail" loading="lazy" referrerpolicy="no-referrer"></td>
                            <td><strong>{{ item.title }}</strong><small>#{{ item.id }} · {{ item.source === 'upload' ? 'Uploaded photo' : 'Image URL' }}</small></td>
                            <td><small v-for="placement in item.placements || defaults" :key="placement">{{ placementTitle(placement) }}</small></td>
                            <td>{{ label(item.media_type) }}<small>{{ busClass(item.bus_class_id) }}</small></td>
                            <td>{{ item.sort_order }}</td>
                            <td><span class="ma-badge" :class="item.is_active ? 'active' : 'disabled'">{{ item.is_active ? 'Published' : 'Hidden' }}</span></td>
                            <td>{{ time(item.updated_at) }}</td>
                            <td><button v-if="options.permissions.content_update" class="btn btn-outline-success btn-sm" @click="edit(item)">Edit image</button><span v-else class="ma-muted">View only</span></td>
                        </tr>
                        <tr v-if="!items.length&&!error"><td colspan="8" class="ma-empty"><i class="fas fa-images"></i>No images match these filters.</td></tr>
                    </template>
                </tbody>
            </table></div>
            <MobilePagination :pagination="pagination" :busy="loading" @change="load" />
        </div>
        <MobileDialog v-if="form" :title="form.id ? 'Edit image' : 'Add image'" :busy="saving" @close="close">
            <div v-if="formError" class="ma-error" role="alert">{{ formError }}</div>
            <form class="ma-form" @submit.prevent="save">
                <div class="ma-field"><label for="ma-content-title">Image title</label><input id="ma-content-title" class="form-control" v-model.trim="form.title" maxlength="150" :disabled="saving" required></div>
                <fieldset class="ma-media-fieldset">
                    <legend>Picture</legend>
                    <div class="ma-actions">
                        <label class="ma-check"><input type="radio" v-model="source" value="upload" :disabled="saving" @change="clearFile"><span>Upload picture</span></label>
                        <label class="ma-check"><input type="radio" v-model="source" value="url" :disabled="saving" @change="clearFile"><span>Use image URL</span></label>
                    </div>
                    <div v-if="source === 'upload'" class="ma-field">
                        <label for="ma-content-file">Choose picture</label>
                        <input id="ma-content-file" ref="fileInput" class="form-control" type="file" accept="image/jpeg,image/png,image/webp" :disabled="saving" @change="chooseFile">
                        <p class="ma-muted">JPG, PNG or WebP · up to 5 MB · 32–4096 pixels per side. A wide image works best for home banners and promotions.</p>
                        <small v-if="file">{{ file.name }} · {{ (file.size / 1024 / 1024).toFixed(2) }} MB</small>
                        <small v-else-if="form.image_url">The current image will stay unless you choose a replacement.</small>
                    </div>
                    <div v-else class="ma-field"><label for="ma-content-url">HTTPS image URL</label><input id="ma-content-url" class="form-control" v-model.trim="form.image_url" type="url" maxlength="2048" :disabled="saving" required><p class="ma-muted">Use a publicly accessible HTTPS image address.</p></div>
                    <figure v-if="previewUrl" class="ma-media-preview">
                        <img v-if="!previewFailed" :src="previewUrl" :alt="form.title || 'Selected image preview'" referrerpolicy="no-referrer" @error="previewFailed = true">
                        <p v-else class="ma-muted">This image could not be previewed. Check the file or address.</p>
                        <figcaption>Image preview · your selected placements control where it appears.</figcaption>
                    </figure>
                </fieldset>
                <fieldset class="ma-media-fieldset">
                    <legend>Show in the app</legend>
                    <div class="ma-grid">
                        <label v-for="placement in placements" :key="placement.key" class="ma-check ma-placement-option">
                            <input type="checkbox" v-model="form.placements" :value="placement.key" :disabled="saving">
                            <span><strong>{{ placement.title }}</strong><small>{{ placement.description }}</small></span>
                        </label>
                    </div>
                    <p class="ma-muted">Choose one or more places. Publishing takes effect when the updated app refreshes its content.</p>
                </fieldset>
                <div class="ma-grid">
                    <div class="ma-field"><label for="ma-content-type">Image category</label><select id="ma-content-type" class="form-control" v-model="form.media_type" :disabled="saving"><option v-for="type in ['exterior','interior','seats','lounge']" :key="type" :value="type">{{ label(type) }}</option></select></div>
                    <div class="ma-field"><label for="ma-content-class">Bus class</label><select id="ma-content-class" class="form-control" v-model="form.bus_class_id" :disabled="saving"><option value="">All bus classes</option><option v-for="category in busClasses" :key="category.id" :value="category.id">{{ category.name }}</option></select></div>
                    <div class="ma-field"><label for="ma-content-order">Display order</label><input id="ma-content-order" class="form-control" v-model.number="form.sort_order" type="number" min="0" step="1" :disabled="saving" required><small>Lower numbers appear first within each placement.</small></div>
                </div>
                <label class="ma-check"><input type="checkbox" v-model="form.is_active" :disabled="saving"><span>Publish this image in the mobile app</span></label>
                <div class="ma-field"><label for="ma-content-reason">Reason</label><input id="ma-content-reason" class="form-control" v-model.trim="reason" minlength="3" maxlength="1000" :disabled="saving" required placeholder="Explain this content change"></div>
                <div class="ma-actions">
                    <button v-if="form.id" type="button" class="btn btn-outline-danger" :disabled="saving||reason.length<3" @click="remove">Remove image</button>
                    <div class="ma-actions"><button type="button" class="btn btn-outline-secondary" :disabled="saving" @click="close">Cancel</button><button class="btn btn-success" :disabled="saving||reason.length<3||!form.placements.length">{{ saving ? 'Saving image…' : 'Save image' }}</button></div>
                </div>
            </form>
        </MobileDialog>
    </div>
</template>
<script>
import MobileFilters from './MobileFilters.vue';
import MobilePagination from './MobilePagination.vue';
import MobileDialog from './MobileDialog.vue';
import { request, label, displayTime } from './mobileApp';

export default {
    components: { MobileFilters, MobilePagination, MobileDialog }, props: { options: Object },
    data: () => ({
        filters: { search: '', status: '' }, applied: null, items: [], pagination: null, busClasses: [], loading: false,
        error: '', notice: '', sequence: 0, form: null, baseline: '', reason: '', saving: false, formError: '',
        file: null, filePreview: '', source: 'upload', previewFailed: false,
        defaults: ['home_carousel', 'bus_guide'],
        placements: [
            { key: 'home_banner', title: 'Home banner', description: 'Wide images at the top of the home screen.' },
            { key: 'home_carousel', title: 'Home photo carousel', description: 'Photos in the home screen’s scrolling gallery.' },
            { key: 'bus_guide', title: 'Bus & Seat Guide', description: 'Fleet, interiors, seats and lounge gallery.' },
            { key: 'home_promotion', title: 'Home promotions', description: 'Travel highlights below the home shortcuts.' },
        ],
    }),
    computed: { previewUrl() { return this.source === 'upload' && this.filePreview ? this.filePreview : this.form?.image_url || ''; } },
    watch: { previewUrl() { this.previewFailed = false; } },
    mounted() { this.load(1, true); },
    beforeUnmount() { this.sequence++; this.clearFile(); },
    methods: {
        label, time: displayTime,
        placementTitle(key) { return this.placements.find(item => item.key === key)?.title || label(key); },
        busClass(id) { return this.busClasses.find(item => Number(item.id) === Number(id))?.name || 'All bus classes'; },
        async load(page = 1, apply = false) {
            if (apply || !this.applied) this.applied = { ...this.filters };
            const sequence = ++this.sequence; this.loading = true; this.error = ''; this.items = [];
            try {
                const data = await request(this, 'get', 'content', null, { ...this.applied, is_active: this.applied.status === 'active' ? 1 : this.applied.status === 'inactive' ? 0 : undefined, page });
                if (sequence === this.sequence) { this.items = data.items; this.pagination = data.pagination; this.busClasses = data.bus_classes || []; }
            } catch (error) { if (sequence === this.sequence) { this.error = error.message; this.pagination = null; } }
            finally { if (sequence === this.sequence) this.loading = false; }
        },
        clearFile() { if (this.filePreview) URL.revokeObjectURL(this.filePreview); this.file = null; this.filePreview = ''; if (this.$refs?.fileInput) this.$refs.fileInput.value = ''; },
        chooseFile(event) {
            const file = event.target.files?.[0]; this.clearFile(); this.formError = '';
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
                this.formError = 'Choose a JPG, PNG or WebP picture no larger than 5 MB.'; return;
            }
            this.file = file; this.filePreview = URL.createObjectURL(file); this.previewFailed = false;
        },
        edit(item = null) {
            this.clearFile();
            this.form = item ? { ...item, placements: [...(item.placements || this.defaults)], bus_class_id: item.bus_class_id ?? '', is_active: !!item.is_active }
                : { title: '', image_url: '', media_type: 'exterior', bus_class_id: '', sort_order: 0, is_active: true, placements: [...this.defaults] };
            this.source = item?.source === 'url' ? 'url' : 'upload';
            this.baseline = JSON.stringify(this.form); this.reason = ''; this.formError = ''; this.previewFailed = false;
        },
        confirmLeave() { return !this.saving && (!this.form || (!this.file && JSON.stringify(this.form) === this.baseline) || window.confirm('Leave without saving the image?')); },
        close() { if (!this.confirmLeave()) return; this.clearFile(); this.form = null; },
        async save() {
            if (this.saving || this.reason.length < 3) return;
            if (!this.form.placements.length) { this.formError = 'Choose at least one app placement.'; return; }
            if (!this.file && !this.form.image_url) { this.formError = 'Choose a picture or enter an HTTPS image URL.'; return; }
            this.saving = true; this.formError = '';
            try {
                let payload = { ...Object.fromEntries(['title', 'media_type', 'sort_order', 'is_active', 'placements'].map(key => [key, this.form[key]])), bus_class_id: this.form.bus_class_id || null, reason: this.reason };
                if (this.form.id) payload.revision = this.form.revision;
                let method = this.form.id ? 'put' : 'post';
                if (this.file) {
                    const multipart = new FormData();
                    for (const [key, value] of Object.entries(payload)) {
                        if (Array.isArray(value)) value.forEach(item => multipart.append(`${key}[]`, item));
                        else multipart.append(key, value === null ? '' : typeof value === 'boolean' ? (value ? '1' : '0') : value);
                    }
                    multipart.append('image', this.file);
                    if (this.form.id) multipart.append('_method', 'PUT');
                    payload = multipart; method = 'post';
                } else payload.image_url = this.form.image_url;
                await request(this, method, this.form.id ? `content/${this.form.id}` : 'content', payload);
                this.notice = 'Image saved. Published images appear in their selected places when the app refreshes.';
                this.clearFile(); this.form = null; await this.load(this.pagination?.current_page || 1);
            } catch (error) { this.formError = error.message; }
            finally { this.saving = false; }
        },
        async remove() {
            if (this.saving || this.reason.length < 3 || !window.confirm('Remove this image from all its mobile app placements?')) return;
            this.saving = true; this.formError = '';
            try {
                await request(this, 'delete', `content/${this.form.id}`, { revision: this.form.revision, reason: this.reason });
                this.notice = 'Image removed from app content.'; this.clearFile(); this.form = null; await this.load(this.pagination?.current_page || 1);
            } catch (error) { this.formError = error.message; }
            finally { this.saving = false; }
        },
    },
};
</script>
