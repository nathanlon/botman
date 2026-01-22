import { config } from '@vue/test-utils'
import { vi } from 'vitest'

// Mock PrimeVue components
config.global.stubs = {
  'router-link': true,
  'router-view': true,
}

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
Object.defineProperty(window, 'localStorage', { value: localStorageMock })

// Mock window.confirm
vi.stubGlobal('confirm', vi.fn(() => true))

// Mock window.alert
vi.stubGlobal('alert', vi.fn())
