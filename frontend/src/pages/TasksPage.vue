<template>
  <v-container class="py-6 px-4" max-width="1280">
    <!-- Page Header & Action -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-6 ga-3">
      <div>
        <h1 class="text-h4 font-weight-bold text-high-emphasis">
          Task Management
        </h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          {{ authStore.isAdmin ? 'Manage, track, and assign tasks across your entire team.' : 'View and update your personal assigned tasks.' }}
        </p>
      </div>

      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        size="large"
        rounded="lg"
        elevation="2"
        @click="openCreateDialog"
      >
        New Task
      </v-btn>
    </div>

    <!-- Filters & Search Toolbar -->
    <v-card rounded="lg" elevation="1" class="pa-4 mb-6">
      <v-row dense align="center">
        <!-- Search Field -->
        <v-col cols="12" md="4" sm="6">
          <v-text-field
            v-model="searchInput"
            placeholder="Search tasks by title or description..."
            variant="outlined"
            density="compact"
            prepend-inner-icon="mdi-magnify"
            clearable
            hide-details
            @update:model-value="onSearchInput"
          />
        </v-col>

        <!-- Status Filter -->
        <v-col cols="12" md="3" sm="6">
          <v-select
            v-model="taskStore.filters.status"
            :items="statusFilterOptions"
            item-title="title"
            item-value="value"
            label="Filter by Status"
            variant="outlined"
            density="compact"
            hide-details
            prepend-inner-icon="mdi-filter-variant"
            @update:model-value="applyFilters"
          />
        </v-col>

        <!-- Assignee Filter (Admin Only) -->
        <v-col v-if="authStore.isAdmin" cols="12" md="3" sm="6">
          <v-select
            v-model="taskStore.filters.assigned_to"
            :items="assigneeFilterOptions"
            item-title="name"
            item-value="id"
            label="Filter by Assignee"
            variant="outlined"
            density="compact"
            hide-details
            prepend-inner-icon="mdi-account-filter"
            @update:model-value="applyFilters"
          />
        </v-col>

        <!-- Refresh / Reset -->
        <v-col cols="12" :md="authStore.isAdmin ? 2 : 5" sm="6" class="text-right">
          <v-btn
            variant="text"
            color="secondary"
            prepend-icon="mdi-refresh"
            size="small"
            :loading="taskStore.loading"
            @click="resetFilters"
          >
            Reset
          </v-btn>
        </v-col>
      </v-row>
    </v-card>

    <!-- Data Table Card -->
    <v-card rounded="lg" elevation="1">
      <v-table hover>
        <thead>
          <tr>
            <th class="text-left font-weight-bold" style="min-width: 250px;">Title</th>
            <th class="text-left font-weight-bold" style="min-width: 170px;">Assignee</th>
            <th class="text-left font-weight-bold" style="min-width: 140px;">Status</th>
            <th class="text-left font-weight-bold" style="min-width: 140px;">Due Date</th>
            <th class="text-right font-weight-bold pr-6" style="min-width: 110px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading State Skeleton -->
          <template v-if="taskStore.loading">
            <tr v-for="i in 5" :key="'skeleton-' + i">
              <td class="py-3">
                <v-skeleton-loader type="text" width="60%" class="mb-1" />
                <v-skeleton-loader type="text" width="85%" />
              </td>
              <td>
                <div class="d-flex align-center ga-2">
                  <v-skeleton-loader type="avatar" size="28" />
                  <v-skeleton-loader type="text" width="90px" />
                </div>
              </td>
              <td>
                <v-skeleton-loader type="chip" width="80px" />
              </td>
              <td>
                <v-skeleton-loader type="text" width="90px" />
              </td>
              <td class="text-right pr-6">
                <v-skeleton-loader type="actions" width="70px" class="d-inline-block" />
              </td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="taskStore.tasks.length === 0">
            <td colspan="5" class="text-center py-12">
              <v-icon icon="mdi-clipboard-text-outline" size="56" color="grey-lighten-1" class="mb-2" />
              <div class="text-h6 text-medium-emphasis">No tasks found</div>
              <div class="text-body-2 text-medium-emphasis mb-4">
                Try adjusting your search criteria or create a new task.
              </div>
              <v-btn color="primary" variant="outlined" size="small" @click="openCreateDialog">
                Create First Task
              </v-btn>
            </td>
          </tr>

          <!-- Task Rows -->
          <template v-else>
          <tr v-for="task in taskStore.tasks" :key="task.id">
            <!-- Title & Description -->
            <td class="py-3">
              <div class="font-weight-medium text-body-1 text-high-emphasis">
                {{ task.title }}
              </div>
              <div v-if="task.description" class="text-caption text-medium-emphasis text-truncate" style="max-width: 420px;">
                {{ task.description }}
              </div>
            </td>

            <!-- Assignee -->
            <td>
              <div class="d-flex align-center ga-2">
                <v-avatar size="28" :color="task.assignee?.role === 'admin' ? 'deep-purple' : 'primary'">
                  <span class="text-caption text-white font-weight-bold">
                    {{ getUserInitials(task.assignee?.name || 'User') }}
                  </span>
                </v-avatar>
                <div>
                  <div class="text-body-2 font-weight-medium">
                    {{ task.assignee?.name || 'Unassigned' }}
                  </div>
                  <div v-if="task.assignee?.role === 'admin'" class="text-caption text-deep-purple font-weight-bold">
                    Admin
                  </div>
                </div>
              </div>
            </td>

            <!-- Status Chip -->
            <td>
              <v-chip
                :color="getTaskStatusColor(task.status)"
                variant="flat"
                size="small"
                class="font-weight-bold text-uppercase"
              >
                <v-icon start size="10">mdi-circle</v-icon>
                {{ formatTaskStatus(task.status) }}
              </v-chip>
            </td>

            <!-- Due Date -->
            <td>
              <div
                v-if="task.due_date"
                class="d-flex align-center ga-1 text-body-2"
                :class="isTaskOverdue(task.due_date, task.status) ? 'text-error font-weight-medium' : 'text-medium-emphasis'"
              >
                <v-icon size="16" :color="isTaskOverdue(task.due_date, task.status) ? 'error' : 'grey'">
                  mdi-calendar-clock
                </v-icon>
                {{ formatTaskDate(task.due_date) }}
                <v-chip
                  v-if="isTaskOverdue(task.due_date, task.status)"
                  size="x-small"
                  color="error"
                  variant="flat"
                  class="ml-1"
                >
                  Overdue
                </v-chip>
              </div>
              <span v-else class="text-caption text-disabled">—</span>
            </td>

            <!-- Actions -->
            <td class="text-right pr-4">
              <v-btn
                icon="mdi-pencil"
                variant="text"
                density="comfortable"
                color="primary"
                title="Edit Task"
                @click="openEditDialog(task)"
              />
              <v-btn
                icon="mdi-delete"
                variant="text"
                density="comfortable"
                color="error"
                title="Delete Task"
                @click="confirmDelete(task)"
              />
            </td>
          </tr>
          </template>
        </tbody>
      </v-table>

      <!-- Pagination Footer -->
      <v-divider />
      <div class="d-flex flex-wrap align-center justify-space-between px-6 py-3 ga-3">
        <div class="text-caption text-medium-emphasis">
          Showing {{ taskStore.pagination.from || 0 }} - {{ taskStore.pagination.to || 0 }} of {{ taskStore.pagination.total }} tasks
        </div>

        <div class="d-flex align-center ga-3">
          <v-select
            v-model="taskStore.filters.per_page"
            :items="[5, 10, 15, 25]"
            density="compact"
            variant="outlined"
            hide-details
            style="width: 90px;"
            @update:model-value="changePerPage"
          />

          <v-pagination
            v-model="taskStore.filters.page"
            :length="taskStore.pagination.last_page"
            :total-visible="5"
            density="comfortable"
            size="small"
            @update:model-value="changePage"
          />
        </div>
      </div>
    </v-card>

    <!-- Create / Edit Dialog -->
    <TaskForm
      v-model="isFormDialogOpen"
      :task="selectedTask"
      @saved="onTaskSaved"
    />

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="isDeleteDialogOpen" max-width="420" persistent>
      <v-card rounded="lg">
        <v-card-item class="bg-error text-white py-3 px-5">
          <v-card-title class="text-h6 font-weight-bold d-flex align-center ga-2">
            <v-icon icon="mdi-alert-circle-outline" />
            Delete Task
          </v-card-title>
        </v-card-item>
        <v-card-text class="pt-5 pb-3 px-5 text-body-1">
          Are you sure you want to delete task
          <strong>"{{ taskToDelete?.title }}"</strong>?
          <div class="text-caption text-medium-emphasis mt-2">
            This task will be soft-deleted and removed from active views.
          </div>
        </v-card-text>
        <v-card-actions class="px-5 py-3">
          <v-spacer />
          <v-btn variant="outlined" color="secondary" @click="isDeleteDialogOpen = false">
            Cancel
          </v-btn>
          <v-btn
            color="error"
            variant="flat"
            prepend-icon="mdi-delete"
            :loading="taskStore.loading"
            @click="executeDelete"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useTaskStore } from '@/stores/tasks'
import { useAuthStore } from '@/stores/auth'
import TaskForm from '@/components/TaskForm.vue'
import {
  TASK_STATUS_OPTIONS,
  formatTaskStatus,
  getTaskStatusColor,
  isTaskOverdue,
  formatTaskDate,
  getUserInitials,
} from '@/utils/task'
import type { Task } from '@/types'

const taskStore = useTaskStore()
const authStore = useAuthStore()

const searchInput = ref<string>('')
let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null

const isFormDialogOpen = ref<boolean>(false)
const selectedTask = ref<Task | null>(null)

const isDeleteDialogOpen = ref<boolean>(false)
const taskToDelete = ref<Task | null>(null)

const statusFilterOptions = [
  { title: 'All Statuses', value: '' },
  ...TASK_STATUS_OPTIONS.map((opt) => ({ title: opt.title, value: opt.value })),
]

const assigneeFilterOptions = computed(() => [
  { name: 'All Assignees', id: '' },
  ...taskStore.users.map((u) => ({ name: u.name, id: u.id })),
])

onMounted(async () => {
  await Promise.all([
    taskStore.fetchTasks(),
    taskStore.fetchUsers(),
  ])
})

onUnmounted(() => {
  if (searchDebounceTimeout) {
    clearTimeout(searchDebounceTimeout)
  }
})

function onSearchInput(val: string | null): void {
  if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout)
  searchDebounceTimeout = setTimeout(() => {
    taskStore.filters.search = val || ''
    taskStore.filters.page = 1
    taskStore.fetchTasks()
  }, 350)
}

function applyFilters(): void {
  taskStore.filters.page = 1
  taskStore.fetchTasks()
}

function resetFilters(): void {
  searchInput.value = ''
  taskStore.filters.search = ''
  taskStore.filters.status = ''
  taskStore.filters.assigned_to = ''
  taskStore.filters.page = 1
  taskStore.fetchTasks()
}

function changePage(page: number): void {
  taskStore.filters.page = page
  taskStore.fetchTasks()
}

function changePerPage(perPage: number): void {
  taskStore.filters.per_page = perPage
  taskStore.filters.page = 1
  taskStore.fetchTasks()
}

function openCreateDialog(): void {
  selectedTask.value = null
  isFormDialogOpen.value = true
}

function openEditDialog(task: Task): void {
  selectedTask.value = { ...task }
  isFormDialogOpen.value = true
}

function onTaskSaved(): void {
  // list refreshes automatically in store actions
}

function confirmDelete(task: Task): void {
  taskToDelete.value = task
  isDeleteDialogOpen.value = true
}

async function executeDelete(): Promise<void> {
  if (taskToDelete.value) {
    const success = await taskStore.deleteTask(taskToDelete.value.id)
    if (success) {
      isDeleteDialogOpen.value = false
      taskToDelete.value = null
    }
  }
}
</script>
