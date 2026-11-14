<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import Button from 'primevue/button';

const props = defineProps({
    // v-model:visible
    visible: { type: Boolean, default: false },
    // Dokumen yang sedang dibuka: { id, name, content }
    document: { type: Object, default: null },
    // Tampilkan loading di tombol Save
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'save']);

// Salinan konten supaya perubahan tidak langsung menimpa data asli sebelum disimpan.
const content = ref('');

watch(
    () => props.document,
    (doc) => {
        content.value = doc?.content ?? '';
    },
    { immediate: true },
);

const close = () => emit('update:visible', false);

const save = () => emit('save', { ...props.document, content: content.value });
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        maximizable
        :draggable="false"
        :style="{ width: '52rem' }"
        :breakpoints="{ '1199px': '80vw', '575px': '95vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <i class="pi pi-file-edit text-xl text-[#1f6f46] dark:text-emerald-400"></i>
                <span class="text-lg font-bold">{{ document?.name }}</span>
            </div>
        </template>

        <Editor v-model="content" editorStyle="height: 22rem" />

        <template #footer>
            <Button label="Close" severity="secondary" variant="outlined" @click="close" />
            <Button
                label="Save"
                icon="pi pi-save"
                :loading="saving"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                @click="save"
            />
        </template>
    </Dialog>
</template>
