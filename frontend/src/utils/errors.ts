import axios from 'axios'

/**
 * Extracts a human-readable error message from an API error response.
 * Handles Laravel validation error dictionaries, HTTP exceptions, and network errors.
 *
 * @param err - Unknown error caught in a try/catch block
 * @param fallbackMessage - Default message if no specific error message can be resolved
 * @returns Clean, user-friendly error string
 */
export function extractApiErrorMessage(err: unknown, fallbackMessage: string): string {
  if (axios.isAxiosError(err)) {
    const data = err.response?.data

    if (data && typeof data === 'object') {
      // Check for Laravel validation error bag: { errors: { field: ["message"] } }
      if ('errors' in data && data.errors && typeof data.errors === 'object') {
        const errors = data.errors as Record<string, string[]>
        const firstField = Object.keys(errors)[0]
        if (firstField && errors[firstField]?.length) {
          return errors[firstField][0]
        }
      }

      // Check for standard exception message: { message: "..." }
      if ('message' in data && typeof data.message === 'string' && data.message.trim()) {
        return data.message
      }
    }

    if (err.message && err.message !== 'Network Error') {
      return err.message
    }
  } else if (err instanceof Error && err.message) {
    return err.message
  }

  return fallbackMessage
}
