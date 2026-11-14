<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import MultiSelect from 'primevue/multiselect';
import Button from 'primevue/button';

const props = defineProps({
    visible: { type: Boolean, default: false },
    document: { type: Object, default: null },
    // [{ id, name, code }]
    teams: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'submit']);

const selectedTeamIds = ref([]);

watch(
    () => [props.document, props.visible],
    ([doc, isOpen]) => {
        if (isOpen && doc) {
            selectedTeamIds.value = (doc.teams ?? []).map((t) => t.id);
        }
    },
    { immediate: true },
);

const close = () => emit('update:visible', false);

const onSubmit = () => {
    if (!props.document) return;
    emit('submit', {
        document: props.document,
        teamIds: selectedTeamIds.value,
    });
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :draggable="false"
        :style="{ width: '30rem' }"
        :breakpoints="{ '640px': '92vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <i class="pi pi-users text-[#1f6f46] dark:text-emerald-400"></i>
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">Assign Teams</span>
            </div>
        </template>

        <form class="space-y-4 pt-1" @submit.prevent="onSubmit">
            <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                Select which teams can view and edit
                <span class="font-semibold text-slate-900 dark:text-surface-0">{{ document?.name }}</span>:
            </p>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    Assigned Teams
                </label>
                <MultiSelect
                    v-model="selectedTeamIds"
                    :options="teams"
                    option-label="name"
                    option-value="id"
                    filter
                    display="chip"
                    placeholder="Select one or more teams..."
                    empty-message="No active teams found"
                    fluid
                    class="!rounded-xl"
                />
                <small class="text-slate-400 dark:text-surface-500 text-xs">
                    You can assign this document to multiple teams at once.
                </small>
            </div>
        </form>

        <template #footer>
            <Button label="Cancel" severity="secondary" variant="outlined" @click="close" />
            <Button
                label="Save Assignment"
                icon="pi pi-check"
                :loading="loading"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold"
                @click="onSubmit"
            />
        </template>
    </Dialog>
</template>
