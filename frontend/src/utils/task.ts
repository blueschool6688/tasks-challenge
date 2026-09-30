import type { TaskStatus } from '@/types'

export interface StatusOption {
  title: string
  value: TaskStatus
  color: string
}

export const TASK_STATUS_OPTIONS: readonly StatusOption[] = [
  { title: 'To Do', value: 'todo', color: 'grey-darken-1' },
  { title: 'In Progress', value: 'in_progress', color: 'primary' },
  { title: 'Done', value: 'done', color: 'success' },
] as const

/**
 * Returns user-friendly title for a task status.
 */
export function formatTaskStatus(status: TaskStatus): string {
  switch (status) {
    case 'todo':
      return 'To Do'
    case 'in_progress':
      return 'In Progress'
    case 'done':
      return 'Done'
    default:
      return status
  }
}

/**
 * Returns Vuetify theme color for a task status.
 */
export function getTaskStatusColor(status: TaskStatus): string {
  switch (status) {
    case 'todo':
      return 'grey-darken-1'
    case 'in_progress':
      return 'primary'
    case 'done':
      return 'success'
    default:
      return 'grey'
  }
}

/**
 * Determines whether a task is overdue relative to today.
 * Completed tasks ('done') are never considered overdue.
 */
export function isTaskOverdue(dueDate: string | null | undefined, status: TaskStatus): boolean {
  if (!dueDate || status === 'done') {
    return false
  }

  const due = new Date(dueDate)
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return due < today
}

/**
 * Formats ISO date string to readable format e.g. "Oct 15, 2026".
 */
export function formatTaskDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'

  try {
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr

    return d.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  } catch {
    return dateStr
  }
}

/**
 * Generates user avatar initials (up to 2 letters).
 */
export function getUserInitials(name: string): string {
  if (!name || !name.trim()) return 'U'

  return name
    .trim()
    .split(/\s+/)
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
