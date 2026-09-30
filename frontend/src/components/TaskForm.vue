<template>
  <v-dialog
    :model-value="modelValue"
    max-width="600px"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card rounded="lg" elevation="4">
      <v-card-item class="bg-primary text-white py-3 px-5">
        <div class="d-flex align-center justify-space-between w-100">
          <v-card-title class="text-h6 font-weight-bold d-flex align-center ga-2">
            <v-icon :icon="isEditMode ? 'mdi-pencil-box' : 'mdi-plus-box'" />
            {{ isEditMode ? 'Edit Task' : 'Create New Task' }}
          </v-card-title>
          <v-btn
            icon="mdi-close"
            variant="text"
            density="compact"
            color="white"
            @click="closeDialog"
          />
        </div>
      </v-card-item>

      <v-card-text class="pt-5 pb-2 px-5">
        <v-form ref="formRef" v-model="isFormValid" @submit.prevent="submitForm">
          <v-text-field
            v-model="form.title"
            label="Title *"
            placeholder="Enter task title"
            variant="outlined"
            density="comfortable"
            :rules="[rules.required, rules.maxLength(255)]"
            prepend-inner-icon="mdi-format-title"
            class="mb-3"
            required
          />

          <v-textarea
            v-model="form.description"
            label="Description"
            placeholder="Provide task details or context"
            variant="outlined"
            density="comfortable"
            rows="3"
            prepend-inner-icon="mdi-text-box-outline"
            class="mb-3"
            auto-grow
          />

          <v-row dense>
            <v-col cols="12" sm="6">
              <v-select
                v-model="form.status"
                :items="statusOptions"
                item-title="title"
                item-value="value"
                label="Status *"
                variant="outlined"
                density="comfortable"
                :rules="[rules.required]"
                prepend-inner-icon="mdi-list-status"
                required
              >
                <template #item="{ props: itemProps, item }">
                  <v-list-item v-bind="itemProps">
                    <template #prepend>
                      <v-icon :color="item.raw.color" size="18" class="mr-2">
                        mdi-circle
                      </v-icon>
                    </template>
                  </v-list-item>
                </template>
              </v-select>
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.due_date"
                label="Due Date"
                type="date"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-calendar"
                clearable
              />
            </v-col>
          </v-row>

          <v-select
            v-model="form.assigned_to"
            :items="userOptions"
            item-title="name"
            item-value="id"
            label="Assignee *"
            variant="outlined"
            density="comfortable"
            :rules="[rules.required]"
            :disabled="!authStore.isAdmin"
            prepend-inner-icon="mdi-account-arrow-right"
            :hint="!authStore.isAdmin ? 'Regular users can only assign tasks to themselves' : ''"
            persistent-hint
            class="mt-2 mb-2"
            required
          >
            <template #item="{ props: itemProps, item }">
              <v-list-item v-bind="itemProps" :subtitle="item.raw.email">
                <template #append>
                  <v-chip size="x-small" :color="item.raw.role === 'admin' ? 'deep-purple' : 'grey'">
                    {{ item.raw.role }}
                  </v-chip>
                </template>
              </v-list-item>
            </template>
          </v-select>
        </v-form>
      </v-card-text>

      <v-divider />

      <v-card-actions class="px-5 py-3">
        <v-spacer />
        <v-btn
          variant="outlined"
          color="secondary"
          @click="closeDialog"
          :disabled="taskStore.loading"
        >
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          prepend-icon="mdi-content-save"
          :loading="taskStore.loading"
          :disabled="!isFormValid"
          @click="submitForm"
        >
          {{ isEditMode ? 'Update Task' : 'Create Task' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useTaskStore } from '@/stores/tasks'
import { useAuthStore } from '@/stores/auth'
import type { Task, TaskStatus, TaskFormPayload } from '@/types'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    task?: Task | null
  }>(),
  {
    task: null,
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved'): void
}>()

const taskStore = useTaskStore()
const authStore = useAuthStore()

const formRef = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
const isFormValid = ref<boolean>(false)

const isEditMode = computed<boolean>(() => !!props.task?.id)

const statusOptions = [
  { title: 'To Do', value: 'todo' as TaskStatus, color: 'grey-darken-1' },
  { title: 'In Progress', value: 'in_progress' as TaskStatus, color: 'blue' },
  { title: 'Done', value: 'done' as TaskStatus, color: 'green' },
]

const userOptions = computed(() => taskStore.users)

const form = reactive<{
  title: string
  description: string
  status: TaskStatus
  assigned_to: number | null
  due_date: string
}>({
  title: '',
  description: '',
  status: 'todo',
  assigned_to: null,
  due_date: '',
})

const rules = {
  required: (v: unknown): boolean | string => !!v || 'This field is required.',
  maxLength: (max: number) => (v: string): boolean | string =>
    !v || v.length <= max || `Must be ${max} characters or less.`,
}

watch(
  () => props.task,
  (newTask) => {
    if (newTask) {
      form.title = newTask.title || ''
      form.description = newTask.description || ''
      form.status = newTask.status || 'todo'
      form.assigned_to = newTask.assigned_to || null
      form.due_date = newTask.due_date ? newTask.due_date.slice(0, 10) : ''
    } else {
      resetForm()
    }
  },
  { immediate: true }
)

function resetForm(): void {
  form.title = ''
  form.description = ''
  form.status = 'todo'
  form.assigned_to = authStore.user?.id || null
  form.due_date = ''
}

function closeDialog(): void {
  emit('update:modelValue', false)
}

async function submitForm(): Promise<void> {
  if (formRef.value) {
    const { valid } = await formRef.value.validate()
    if (!valid) return
  }

  if (form.assigned_to === null) {
    taskStore.notify('Please select an assignee.', 'error')
    return
  }

  const payload: TaskFormPayload = {
    title: form.title.trim(),
    description: form.description?.trim() || null,
    status: form.status,
    assigned_to: form.assigned_to,
    due_date: form.due_date ? form.due_date : null,
  }

  let success = false
  if (isEditMode.value && props.task?.id) {
    success = await taskStore.updateTask(props.task.id, payload)
  } else {
    success = await taskStore.createTask(payload)
  }

  if (success) {
    emit('saved')
    closeDialog()
  }
}
</script>
